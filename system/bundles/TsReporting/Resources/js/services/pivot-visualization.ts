import type { QueryDefinitionItems, QueryRow, QueryRows, QueryRowValue, QueryRowValueColumn } from '../types'
import { Definition, HeadCell, NullCell, SumCell, ValueCell } from '../composables/visualization'

type PivotCorrelation = Map<QueryRowValue, PivotCorrelation|null>
type GroupingPath = QueryRowValue[][]
type HeadCellOrNull = HeadCell | NullCell
type ValueCellOrNull = ValueCell | NullCell
type TotalsSumMap = Map<string, number>

export class PivotConfig {
	showGrandTotals = true
	showRowTotals = true
	labelGrandTotals = 'Grand totals'
	labelRowTotals = 'Row totals'
	labelSubTotals = 'Subtotals for {label}'
}

export class PivotTable {
	private config: PivotConfig
	private groupingRows: Definition[] = []
	private groupingCols: Definition[] = []
	private colsDefined: Definition[] = []
	private colsVirtual: HeadCell[] = []
	private colsCorrelation: PivotCorrelation = new Map()
	private colsTotals: TotalsSumMap = new Map()
	private hasColumnLabels = false
	private hasSummableColumns = false

	constructor(definitions: Definition[], config: PivotConfig) {
		this.config = config
		this.prepareDefinitions(definitions)
	}

	prepareDefinitions(definitions: Definition[]) {
		definitions.forEach(d => {
			if (d.type === 'grouping') {
				if (d.pivot === 'row') {
					return this.groupingRows.push(d)
				}
				return this.groupingCols.push(d)
			}
			return this.colsDefined.push(d)
		})
		if (!this.groupingRows.length && this.groupingCols.length) {
			throw new Error('Pivot needs at least groupings on row and col axis')
		}
		if (!this.colsDefined.length) {
			throw new Error('No column definition exists')
		}
		if (this.colsDefined.length > 1) {
			// Wenn mehr als eine Spalte: Fake-Grouping ergänzen
			const items = this.colsDefined.map(c => [c.key, c.label, null]) as QueryDefinitionItems
			this.groupingCols.push(new Definition({ key: 'COLUMNS', label: '', type: 'column', items }, -1))
			this.hasColumnLabels = true
		}
		this.hasSummableColumns = this.colsDefined.some(d => d.format?.summable)
	}

	generate(rows: QueryRows) {
		rows = this.generateMissingGroupingValues(rows)
		this.generateColCorrelation(rows)
		const head = this.generateHead()
		const body = Array.from(this.prepareData(rows).values())
		const foot = body.length > 1 ? this.generateFoot() : []
		return [head, body, foot]
	}

	/**
	 * Spalten aus Definition.items generieren, welche von keiner Zeile abgedeckt werden (nur relevant für Pivot)
	 *
	 * Beispiel: Period, sodass chronologisch alle Zeiträume sichtbar sind
	 */
	private generateMissingGroupingValues(rows: QueryRows) {
		const rows2 = [...rows]
		this.groupingRows.concat(this.groupingCols)
			.filter(d => d.type === 'grouping')
			.forEach(def => {
				def.items.forEach(item => {
					if (!rows.some(r => r[def.index] === item[0])) {
						// Erstbeste Row klonen und alle Werte zurücksetzen
						const row = [...rows[0]].fill(['', ''], this.colsDefined[0].index)
						row[def.index] = item[0]
						rows2.push(row)
					}
				})
			})
		return rows2
	}

	/**
	 * Gruppierungen untereinander verknüpfen: Welche Werte werden bei welchen Gruppierungen überhaupt angezeigt?
	 */
	private generateColCorrelation(rows: QueryRows) {
		rows.forEach(row => {
			let c = this.colsCorrelation
			this.groupingCols.forEach(def => {
				if (this.hasColumnLabels && def.type === 'column') {
					// Spaltengruppierung für Columns: Alle hinzufügen
					def.items.forEach(i => c.set(i[0], null))
					return
				}
				const cell = row[def.index]
				if (!c.has(cell)) {
					c.set(cell, new Map())
				}
				// Map.has()/Map.set() ist kein Guard in Typescript
				// @ts-ignore
				c = c.get(cell)
			})
		})
	}

	private prepareData(rows: QueryRows) {
		// Stacks für Zeilengruppierungen
		const lastRowGroupDataValues = new Array<QueryRowValue>(this.groupingRows.length)
		const lastRowGroupDataRef = new Array<ValueCell>(this.groupingRows.length)
		const rowTotals: TotalsSumMap = new Map()
		const subTotals: TotalsSumMap = new Map()
		const setTotals = (map: TotalsSumMap, key: string, tuple: QueryRowValueColumn) => map.set(key, (map.get(key) ?? 0) + (tuple[0] as number))
		const buildRowKey = (row: QueryRow) => this.groupingRows.map(d => row[d.index]).join(':')

		// Zeilengruppierungen sortieren, ansonsten wäre Reihenfolge ggf. falsch und Summen durcheinander
		this.sortRowsByRowGroupings(rows)

		return rows.reduce((map, row: QueryRow, rowIndex) => {
			const rowKey = buildRowKey(row)

			let dataRow = map.get(rowKey)
			if (!dataRow) {
				dataRow = this.createDataRow(lastRowGroupDataValues, lastRowGroupDataRef, row)
				map.set(rowKey, dataRow)
			}

			for (const colDef of this.colsDefined) {
				// Column/Cell, in welche der Wert eingefügt werden muss
				const groupingKey = this.groupingCols.filter(d => d.type === 'grouping').map(d => row[d.index]).join(':')
				const colKey = this.hasColumnLabels ? `c:${groupingKey}:${colDef.key}` : `c:${groupingKey}`
				const absIndex = this.colsVirtual.findIndex(c => c && c.key === colKey)

				if (absIndex < 0) {
					throw new Error('Data grouping error (data column does not exist)')
				}
				if (!dataRow[absIndex].isEmpty()) {
					// Aktuell sollte der Merge komplett die API übernehmen
					throw new Error('Data grouping error (grouping column already exists in grouping row)')
				}
				if (!Array.isArray(row[colDef.index])) {
					throw new Error('Column value is not an array (QueryRowValueColumn)')
				}
				const tuple = row[colDef.index] as QueryRowValueColumn
				dataRow[absIndex] = new ValueCell(tuple[0], colDef)
				dataRow[absIndex].key = colKey // Debug
				dataRow[absIndex].label = tuple[1]

				// Werte für Zeilensumme und Gesamtsumme
				if (colDef.format?.summable && typeof tuple[0] === 'number') {
					setTotals(rowTotals, `${rowKey}:${colDef.key}`, tuple) // Zeilensumme (rechts)
					setTotals(this.colsTotals, colKey, tuple) // Gesamtsumme Spalte (unten(
					setTotals(this.colsTotals, this.hasColumnLabels ? `c:${colDef.key}:rowsum` : 'c:rowsum', tuple) // Gesamtsumme Zeilensumme (rechts unten)
				}

				// Werte für Zwischensummen pro Zeilengruppierung
				this.groupingRows.forEach(groupingDef => {
					if (colDef.format?.summable && groupingDef.subtotals && typeof tuple[0] === 'number') {
						setTotals(subTotals, `${groupingDef.key}-${colKey}`, tuple) // Zwischensumme (unter Zeile)
						setTotals(subTotals, this.hasColumnLabels ? `${groupingDef.key}-c:${colDef.key}:rowsum` : `${groupingDef.key}-c:rowsum`, tuple) // Zeilensumme der Zwischensumme
					}
				})
			}

			const nextRow = rows[rowIndex + 1] ?? []
			const nextRowKey = buildRowKey(nextRow)

			// Zeilensummen
			if (rowKey !== nextRowKey) {
				for (const colDef of this.colsDefined) {
					const sumKeyRow = `${rowKey}:${colDef.key}`
					const absIndex = this.colsVirtual.findIndex(c => c && c.key === (this.hasColumnLabels ? `c:${colDef.key}:rowsum` : 'c:rowsum'))
					dataRow[absIndex] = new SumCell(rowTotals.get(sumKeyRow) ?? null, colDef)
					dataRow[absIndex].key = sumKeyRow // Debug
					rowTotals.delete(sumKeyRow)
				}
			}

			// Zwischensummen
			const subTotalRows = this.createSubTotalRows(row, nextRow, rowKey, lastRowGroupDataRef, subTotals)
			subTotalRows.forEach(([k, v]) => map.set(k, v))

			return map
		}, new Map() as Map<string, ValueCellOrNull[]>)
	}

	private sortRowsByRowGroupings(rows: QueryRows) {
		const collator = new Intl.Collator(undefined, { numeric: true })
		rows.sort((a, b) => {
			// Multisort
			for (const def of this.groupingRows) {
				const valueA = def.getItemSortValue(a[def.index]).toString()
				const valueB = def.getItemSortValue(b[def.index]).toString()
				const cmp = collator.compare(valueA, valueB)
				if (cmp !== 0) {
					return cmp
				}
			}
			return 0
		})
	}

	/**
	 * Zeile erzeugen mit bereits allen vorhandenen Zeilengruppierungen plus rowspan und leeren Datenwerten
	 */
	private createDataRow(lastRowGroupDataValues: Array<QueryRowValue|undefined>, lastRowGroupDataRef: ValueCell[], row: QueryRow): ValueCellOrNull[] {
		// Initial Array mit so vielen Items wie es Spalten insgesamt gibt
		const dataRow = this.createTableRow()
		const key = new Array<QueryRowValue>()

		// Werte der Zeilen-Gruppierungen initial und einmalig setzen
		this.groupingRows.forEach((def, i) => {
			if (lastRowGroupDataValues[i] !== row[def.index]) {
				// Neuer Wert (Wechsel) der jeweiligen Gruppierungsebene
				key.push(row[def.index])
				lastRowGroupDataValues[i] = row[def.index]
				lastRowGroupDataRef[i] = new ValueCell(def.getItemLabel(row[def.index]), def)
				lastRowGroupDataRef[i].key = key.join(':') // Debug
				lastRowGroupDataRef[i].rowspan = 1
				dataRow[i] = lastRowGroupDataRef[i]

				// Alle nachfolgenden Gruppierungen zurücksetzen, damit es kein rowspan über die weiteren Zeilen gibt
				Array.from(this.groupingRows.keys()).filter(j => j > i).forEach(j => lastRowGroupDataValues[j] = undefined)
			} else {
				// Gruppierungsebene bleibt gleich, rowspan erhöhen
				// @ts-ignore
				lastRowGroupDataRef[i].rowspan++
				dataRow[i] = NullCell.create()
			}
		})
		return dataRow
	}

	/**
	 * Zwischensummen erzeugen
	 */
	private createSubTotalRows(row: QueryRow, nextRow: QueryRow, rowKey: string, lastRowGroupDataRef: ValueCell[], subTotals: TotalsSumMap) {
		const rows: [string, ValueCellOrNull[]][] = []
		this.groupingRows.forEach((groupingDef, i) => {
			// Wechsel der Zeilengruppierung (quasi das gleiche wie in createDataRow, nur hier am Ende des Durchlaufs einer Datenzeile)
			if (this.hasSummableColumns && groupingDef.subtotals && row[groupingDef.index] !== nextRow[groupingDef.index]) {
				// Zeile vorbereiten
				const r = this.createTableRow()
				r[i] = new SumCell(this.config.labelSubTotals.replace('{label}', groupingDef.getItemLabel(row[groupingDef.index]) as string))
				r[i].colspan = this.groupingRows.length - i
				r.fill(NullCell.create(), 0, i) // Zellen nullen für rowspan davor
				r.fill(NullCell.create(), i + 1, this.groupingRows.length) // Zellen nullen für colspan dahinter
				rows.push([`${rowKey}:subtotal:${groupingDef.key}`, r])

				// Zwischensumme setzen und zurücksetzen
				this.colsVirtual.forEach((c, j) => {
					const sumKey = `${groupingDef.key}-${c.key}`
					if (j > this.groupingRows.length - 1) {
						r[j] = new SumCell(subTotals.get(sumKey) ?? null, this.hasColumnLabels ? c.definition : this.colsDefined[0])
						r[j].key = sumKey // Debug
						subTotals.delete(sumKey)
					}
				})

				// Da ab jeder zweiten Gruppierungsebene eine Zeile eingefügt wird, muss für alle Gruppierungen davor der rowspan erhöht werden
				if (i !== 0) {
					// @ts-ignore
					Array.from(this.groupingRows.keys()).filter(j => j < i).forEach(j => lastRowGroupDataRef[j].rowspan++)
				}
			}
		})
		return rows.reverse()
	}

	/**
	 * Array mit allen notwendigen Spalten pro Zeile, d.h. alle td für ein tr
	 * Objekte müssen bei Veränderung immer neu erstellt werden, da fill die gleiche Instanz setzt
	 */
	private createTableRow(cls: typeof ValueCell = ValueCell) {
		return new Array<ValueCellOrNull>(this.colsVirtual.length).fill(new cls(null))
	}

	private generateHead() {
		const rowCells = this.generateGroupingRowCells()
		const colCells = this.generateGroupingColumnCells(this.colsCorrelation)
		const showRowTotals = this.config.showRowTotals && this.hasSummableColumns && this.groupingCols.some(d => d.type === 'grouping' && d.items.length > 1)

		// Jede groupingCol ist eine Zeile im Head
		return this.groupingCols.map((colDef, colIndex) => {
			let row = new Array<HeadCellOrNull>()

			// Zeilengruppierungen
			row = row.concat(rowCells.map(rowDef => {
				if (colIndex === 0) {
					// Nur erste Head-Row hat die Zeilengruppierungen (ansonsten rowspan)
					return rowDef
				}
				return NullCell.create()
			}) as HeadCell[])

			// Spaltengruppierungen: Jede Spaltengruppierung ist eine Zeile im Head
			let last: HeadCell
			let lastKey = ''
			row = row.concat(colCells.reduce((carry, path) => {
				// Aktuellen Pfad extrahieren auf Basis von groupingCols.map oben
				const cellPath = path.slice(0, colIndex + 1)
				const key = `c:${cellPath.join(':')}` // == buildColumnKey()
				if (key !== lastKey) {
					let colDef2 = colDef
					if (this.hasColumnLabels && colDef.type === 'column') {
						// Column-Definition ersetzen mit tatsächlicher Definition für Formatierung in Summe
						colDef2 = this.colsDefined.find(d => d.key === cellPath.at(-1)) ?? colDef
					}
					lastKey = key
					last = new HeadCell(colDef.getItemLabel(path[colIndex] as QueryRowValue), colDef2)
					last.key = key // Matching
					last.path = cellPath
					last.colspan = 1
					last.format = true // Bspw. Datum formatieren
					carry.push(last)
				} else if(last && last.colspan) {
					// Analog zu einer HTML-Tabelle alle Zellen überspringen, die durch colspan wegfallen, aber als null behalten
					carry.push(NullCell.create())
					if (last.colspan) {
						last.colspan++
					}
				}
				return carry
			}, new Array<HeadCellOrNull>()))

			// Zeilensummen rechts
			if (showRowTotals) {
				if (colIndex === 0) {
					// Label analog zu Zeilengruppierungen mit rowspan über die groupingCols
					const rowSums = new HeadCell(this.config.labelRowTotals)
					rowSums.rowspan = this.groupingCols.filter(d => d.type === 'grouping').length
					if (!this.hasColumnLabels) {
						rowSums.key = 'c:rowsum' // Matching
					}
					row.push(rowSums)
				} else if (this.hasColumnLabels && colDef.type === 'column') {
					// Wenn es die Labels für Spalten gibt
					row = row.concat(colDef.items.map(i => {
						const head = new HeadCell(i[1], this.colsDefined.find(d => d.key === i[0]) ?? colDef)
						head.key = `c:${i[0]}:rowsum` // Matching
						return head
					}))
				}
			}

			// Komplette Definition des Aufbaus der Spalten
			// Da von oben nach unten iteriert wird, sollte immer die genauste Definition einer Spalte gesetzt sein
			row.forEach((r, i) => {
				if (r instanceof HeadCell) {
					this.colsVirtual[i] = r
				}
			})

			return row
		})
	}

	private generateGroupingRowCells(): HeadCell[] {
		const path = [] as QueryRowValue[]
		return this.groupingRows.map(rowDef => {
			path.push(rowDef.key)
			const cell = new HeadCell(rowDef.label, rowDef)
			cell.key = `r:${path.join(':')}` // Matching
			cell.path = [...path]
			cell.rowspan = this.groupingCols.length
			return cell
		})
	}

	/**
	 * Auf Basis der Column-Gruppierungen und des PivotCorrelation-Baums alle benötigen Zellen generieren. Jedes Item
	 * enhält den kompletten Pfad (Werte der Gruppierung, z.B. School-ID), wie sich die Gruppierung zusammensetzt.
	 */
	private generateGroupingColumnCells(correlation: PivotCorrelation, index = 0, carry: GroupingPath = [], path: string[] = []): GroupingPath {
		// @ts-ignore
		return this.groupingCols[index].items
			.filter(item => correlation.has(item[0]))
			.reduce((carry, item) => {
				if (this.groupingCols[index + 1]) {
					path.push(item[0])
					const next = this.generateGroupingColumnCells(correlation.get(item[0]) as PivotCorrelation, index + 1, carry, path)
					path.pop()
					return next
				}
				carry.push(path.concat(item[0]))
				return carry
			}, carry)
	}

	private generateFoot() {
		if (!this.config.showGrandTotals || !this.hasSummableColumns) {
			return
		}
		const row = this.createTableRow(SumCell)
		row[0] = new SumCell(this.config.labelGrandTotals)
		row[0].colspan = this.groupingRows.length
		row.fill(NullCell.create(), 1, this.groupingRows.length)
		this.colsVirtual.forEach((c, i) => {
			if (i > this.groupingRows.length - 1) {
				row[i] = new SumCell(this.colsTotals.get(c.key ?? '') ?? null, this.hasColumnLabels ? c.definition : this.colsDefined[0])
				row[i].key = c.key // Debug
			}
		})
		return [row]
	}
}
