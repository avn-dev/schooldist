<?php

namespace Admin\Helper\Welcome;

use Admin\Components\Dashboard\Handler;
use Admin\Instance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class Box
{

    const COLOR_GREEN = 'green';
    const COLOR_BLUE = 'blue';
    const COLOR_YELLOW = 'yellow';
    const COLOR_GRAY = 'gray';

    protected $aBox = [];

    protected $sLanguage;

    /**
     * @var \Monolog\Logger
     */
    protected $oLog;

    public function __construct(array $aBox)
    {

        $this->aBox = $aBox;
        $this->sLanguage = \System::getInterfaceLanguage();

        $this->oLog = \Log::getLogger('dashboard');

    }

    /**
     * @param array $aBox
     * @return self
     */
    public static function getInstance(array $aBox)
    {

        $sClass = \Admin\Helper\Welcome\Box::class;

        if (!empty($aBox['object'])) {
            $sClass = $aBox['object'];
        }

        $oBox = new $sClass($aBox);

        return $oBox;
    }

    public function setLanguage($sLanguage)
    {

        $this->sLanguage = $sLanguage;

        \Factory::executeStatic('System', 'setInterfaceLanguage', [$sLanguage]);
        \Factory::executeStatic('System', 'setLocale');

    }

    protected function getCacheKey()
    {

        $sKey = '';

        if (!empty($this->aBox['object'])) {
            $sKey .= $this->aBox['object'];
        }

        if (!empty($this->aBox['function'])) {
            if (is_array($this->aBox['function'])) {
                foreach ($this->aBox['function'] as $mElement) {
                    if (is_object($mElement)) {
                        $sKey .= get_class($mElement);
                    } else {
                        $sKey .= $mElement;
                    }
                }
            }
        }

        $sCacheKey = 'welcome_' . $sKey . "_" . $this->sLanguage;

        return $sCacheKey;
    }

    public function updateCache(Instance $admin, Request $request) {

        if (!empty($this->aBox['component'])) {

            $request->merge(['force' => true]);

            app()->call([$admin->getComponent($this->aBox['component']), 'init']);

        } else {
            $this->getContent(true);
        }


        $this->oLog->addInfo('Update cache successful ' . $this->getTitle(), [$this->sLanguage]);

    }

    public function getTitle()
    {
        return $this->aBox['title'];
    }

    public function getLastChanged(): ?Carbon
    {

        if (is_numeric($this->aBox['changed'])) {
            return Carbon::createFromTimestamp($this->aBox['changed'], date_default_timezone_get());
        } else if (is_string($this->aBox['changed'])) {
            return Carbon::parse($this->aBox['changed']);
        }

        return null;
    }

    public function getIcon(): ?string
    {
        return $this->aBox['icon'] ?? null;
    }

    public function getColor(): ?string
    {
        return $this->aBox['color'] ?? null;
    }

    public function isPrintable(): bool
    {
        return $this->aBox['print'] ?? false;
    }

    public function generateHtml($fStartTime = null, $bSkipCache = false): string
    {

        ob_start();
        $this->printBox('xxx', $fStartTime, $bSkipCache);
        $content = ob_get_clean();

        return $content;
    }

    public function printBox($sRefreshKey = null, $fStartTime = null, $bSkipCache = false)
    {

        $this->getContent($bSkipCache);

        $sContentClass = 'box-body';
        if (strpos($this->aBox['content'], '<table') !== false) {
            $sContentClass = 'box-body table-responsive no-padding';
        } elseif (
                strpos($this->aBox['content'], '<ul') !== false ||
                ($this->aBox['no_padding'] ?? false) === true
        ) {
            $sContentClass = 'box-body no-padding';
        }

        if (empty($this->aBox['class'])) {
            $this->aBox['class'] = 'box-default';
        }

        /*if(!empty($this->aBox['changed'])) {
            $dChanged = new \DateTime($this->aBox['changed']);
        }*/

        if (!empty($this->aBox['content'])) {
            ?>

            <div class="<?= $sContentClass ?>" style="display: block;">
                <?= $this->aBox['content'] ?>
            </div>
            <?php
        }

        if (
                $fStartTime !== null &&
                \System::d('debugmode') == 2
        ) {
            echo '<!-- Runtime: ' . number_format(microtime(true) - $fStartTime, 6) . '-->';
        }

    }

    /**
     * @param array $this ->aBox
     * @param boolean $bSkipCache
     */
    public function getContent($bSkipCache = false)
    {

        if (!isset($this->aBox['cache_time'])) {
            $this->aBox['cache_time'] = 60 * 60 * 24 * 7;
        }

        // Prüfen, ob Box in Cache geschrieben werden kann
        if (isset($this->aBox['function'])) {

            if ($this->aBox['cache_time'] !== false) {

                $sCacheKey = $this->getCacheKey();
                $aCachedBox = \WDCache::get($sCacheKey, true);

                // Nicht vorhanden, Debug-Parameter oder Refresh-Icon
                if ($bSkipCache === true) {

                    $this->callFunction();

                    $this->aBox['changed'] = Carbon::now()->getTimestamp();

                    \WDCache::set($sCacheKey, $this->aBox['cache_time'], $this->aBox, true);

                } elseif ($aCachedBox === null) {
                    // Es sind keine Daten im Cache, Box wird nicht angezeigt sondern wird übers PP aktualisiert.
                    $this->aBox['changed'] = null;
                    $this->aBox['content'] = '';
                } else {
                    // Box aus dem Cache laden
                    $this->aBox['changed'] = $aCachedBox['changed'];
                    $this->aBox['content'] = $aCachedBox['content'];
                }

            } else {

                $this->callFunction();

            }

        }

    }

    public function callFunction()
    {

        if (!empty($this->aBox['parameter'])) {
            $this->aBox['content'] = call_user_func_array($this->aBox['function'], $this->aBox['parameter']);
        } else {
            $this->aBox['content'] = call_user_func($this->aBox['function']);
        }

    }

    public function getHandler(): Handler
    {
        if (!isset($this->aBox['handler'])) {
            throw new \RuntimeException(sprintf('Missing handler for dashboard box [%s]', $this->getTitle()));
        }

        return $this->aBox['handler'];
    }

    public function getComponent(): ?string
    {
        return $this->aBox['component'] ?? null;
    }
}