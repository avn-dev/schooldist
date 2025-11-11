<?php

use Tc\Enums\EventManager\Process\TaskType;
use Tc\Tests\Unit\EventManager\Stubs;
use Tc\Tests\Unit\EventManager\Stubs\ConditionStub;
use Tc\Tests\Unit\EventManager\Stubs\ListenerStub;
use Tc\Tests\Unit\EventManager\Stubs\Process\ProcessTaskStub;

// TODO mehrere Listeners und Conditions
// TODO Klassen die nicht beim Event angegeben sind dürfen nicht ausgeführt werden
// TODO $eventProcessBinding

beforeEach(function () {
	\Tc\Service\EventPipeline::resetDebug();

	$this->defaultProcess = new Stubs\Process\ProcessStub(
		[new ProcessTaskStub(TaskType::LISTENER, ListenerStub::class)],
		[new ProcessTaskStub(TaskType::CONDITION, ConditionStub::class)]
	);

	$this->container = new Illuminate\Container\Container();
	$this->repository = Mockery::mock(\Tc\Interfaces\EventManager\Repository::class);
	$this->eventManager = (new \Tc\Service\EventManager($this->container, $this->repository))
		->setLogger(new \Psr\Log\NullLogger())
		->setL10N(new \Tc\Service\Language\NullTranslator());
});

test('EventManager - Laravel collection merge behavior', function () {
	$first = collect([1, 2]);
	$second = collect([3, 4]);

	$merge = [...$first, ...$second];

	expect($merge)->toBe([1, 2, 3, 4]);
});

test('EventManager - event list contains registered event', function () {
	$this->repository->shouldReceive('forEvent')->andReturn(collect());

	$this->eventManager->listen(Stubs\EventStub::class);

	expect($this->eventManager->getEventList())->toHaveKey(Stubs\EventStub::class);
});

test('EventManager - event list not contains event when not registered', function () {
	$this->repository->shouldReceive('forEvent')->andReturn(collect());
	expect($this->eventManager->getEventList())->not()->toHaveKey(Stubs\EventStub::class);
});

test('EventManager - listen to registered event', function () {
	$this->repository->shouldReceive('forEvent')->andReturn(collect([$this->defaultProcess]));

	$this->eventManager->listen(Stubs\EventStub::class);

	$this->assertTrue($this->eventManager->isListening(Stubs\EventStub::class));
});

test('EventManager - not listen to unregistered event', function () {
	$this->repository->shouldReceive('forEvent')->andReturn(collect([$this->defaultProcess]));
	$this->assertFalse($this->eventManager->isListening(Stubs\EventStub::class));
});

test('EventManager - event list contains registered event but not listen to event (no processes)', function () {
	$this->repository->shouldReceive('forEvent')->andReturn(collect());

	$this->eventManager->listen(Stubs\EventStub::class);

	$this->assertFalse($this->eventManager->isListening(Stubs\EventStub::class));
});

test('EventManager - turned off', function () {
	$this->repository->shouldReceive('forEvent')->andReturn(collect([$this->defaultProcess]));

	$this->eventManager->listen(Stubs\EventStub::class);
	$this->eventManager->turnOff();

	$this->eventManager->handle(Stubs\EventStub::class, new Stubs\EventStub());

	$this->assertEmpty($this->eventManager->getDebug());
});


test('EventManager - handle event', function () {
	$this->repository->shouldReceive('forEvent')->andReturn(collect([$this->defaultProcess]));

	$this->eventManager->listen(Stubs\EventStub::class);

	$this->eventManager->handle(Stubs\EventStub::class, new Stubs\EventStub());

	$debug = [
		0 => [
			'conditions' => [Stubs\ConditionStub::class => [true]],
			'listeners' => [Stubs\ListenerStub::class => [true]]
		]
	];

	$this->assertSame($this->eventManager->getDebug(Stubs\EventStub::class), $debug);
});

test('EventManager - handle event (multiple processes)', function () {
	$this->repository->shouldReceive('forEvent')->andReturn(collect([
		$this->defaultProcess,
		clone $this->defaultProcess,
	]));

	$this->eventManager->listen(Stubs\EventStub::class);

	$this->eventManager->handle(Stubs\EventStub::class, new Stubs\EventStub());

	$debug = [
		0 => [
			'conditions' => [Stubs\ConditionStub::class => [true]],
			'listeners' => [Stubs\ListenerStub::class => [true]]
		],
		1 => [
			'conditions' => [Stubs\ConditionStub::class => [true]],
			'listeners' => [Stubs\ListenerStub::class => [true]]
		]
	];

	$this->assertSame($this->eventManager->getDebug(Stubs\EventStub::class), $debug);
});

test('EventManager - handle event (multiple processes, same listeners multiple)', function () {
	$this->repository->shouldReceive('forEvent')->andReturn(collect([
		new Stubs\Process\ProcessStub(
			[
				new ProcessTaskStub(TaskType::LISTENER, ListenerStub::class),
				new ProcessTaskStub(TaskType::LISTENER, ListenerStub::class),
			],
			[new ProcessTaskStub(TaskType::CONDITION, ConditionStub::class)]
		)
	]));

	$this->eventManager->listen(Stubs\EventStub::class);

	$this->eventManager->handle(Stubs\EventStub::class, new Stubs\EventStub());

	$debug = [
		0 => [
			'conditions' => [Stubs\ConditionStub::class => [true]],
			'listeners' => [Stubs\ListenerStub::class => [true, true]]
		]
	];

	$this->assertSame($this->eventManager->getDebug(Stubs\EventStub::class), $debug);
});

test('EventManager - event stopped by condition', function () {

	$this->repository->shouldReceive('forEvent')->andReturn(collect([$this->defaultProcess]));

	$condition = Mockery::mock(Stubs\ConditionStub::class);
	$condition->shouldReceive('bindManagedObject')->andReturnSelf();
	$condition->shouldReceive('passes')->andReturn(false);

	$this->container->instance(Stubs\ConditionStub::class, $condition);

	$this->eventManager->listen(Stubs\EventStub::class);

	$this->eventManager->handle(Stubs\EventStub::class, new Stubs\EventStub());

	$debug = [
		0 => [
			'conditions' => [$condition::class => [false]],
			'listeners' => [Stubs\ListenerStub::class => [false]]
		]
	];

	$this->assertSame($this->eventManager->getDebug(Stubs\EventStub::class), $debug);
});

test('EventManager - event stopped by condition (multiple conditions)', function () {

	$this->repository->shouldReceive('forEvent')->andReturn(collect([$this->defaultProcess]));

	$condition = Mockery::mock(Stubs\ConditionStub::class);
	$condition->shouldReceive('bindManagedObject')->andReturnSelf();
	$condition->shouldReceive('passes')->andReturn(false);

	$this->container->instance(Stubs\ConditionStub::class, $condition);

	$this->eventManager->listen(Stubs\EventStub::class);

	$this->eventManager->handle(Stubs\EventStub::class, new Stubs\EventStub());

	$debug = [
		0 => [
			'conditions' => [$condition::class => [false]],
			'listeners' => [Stubs\ListenerStub::class => [false]]
		]
	];

	$this->assertSame($this->eventManager->getDebug(Stubs\EventStub::class), $debug);
});

test('EventManager - event stopped by condition, other processes will run successfully', function () {
	// Wenn der erste Prozess aufgrund der Bedingung nicht ausgeführt wurde muss sichergestellt sein,
	// dass weitere Events trotzdem ausgeführt werden

	$this->repository->shouldReceive('forEvent')->andReturn(collect([
		$this->defaultProcess,
		clone $this->defaultProcess
	]));

	$condition = Mockery::mock(Stubs\ConditionStub::class);
	$condition->shouldReceive('bindManagedObject')->andReturnSelf();
	$condition->shouldReceive('passes')->once()->andReturn(false); // Condition schlägt fehl
	$condition->shouldReceive('passes')->once()->andReturn(true);

	$this->container->instance(Stubs\ConditionStub::class, $condition);

	$this->eventManager->listen(Stubs\EventStub::class);

	$this->eventManager->handle(Stubs\EventStub::class, new Stubs\EventStub());

	$debug = [
		0 => [
			'conditions' => [$condition::class => [false]],
			'listeners' => [Stubs\ListenerStub::class => [false]]
		],
		1 => [
			'conditions' => [$condition::class => [true]],
			'listeners' => [Stubs\ListenerStub::class => [true]]
		]
	];

	$this->assertSame($this->eventManager->getDebug(Stubs\EventStub::class), $debug);
});

test('EventManager - event stopped by condition exception, other processes will run successfully', function () {
	// Wenn der erste Prozess aufgrund der Bedingung nicht ausgeführt wurde muss sichergestellt sein,
	// dass weitere Events trotzdem ausgeführt werden

	$this->repository->shouldReceive('forEvent')->andReturn(collect([
		$this->defaultProcess,
		clone $this->defaultProcess
	]));

	$condition = Mockery::mock(Stubs\ConditionStub::class);
	$condition->shouldReceive('bindManagedObject')->andReturnSelf();
	$condition->shouldReceive('passes')->once()->andThrow(new \RuntimeException('Test')); // Condition schlägt fehl
	$condition->shouldReceive('passes')->once()->andReturn(true);

	$this->container->instance(Stubs\ConditionStub::class, $condition);

	$this->eventManager->listen(Stubs\EventStub::class);

	$this->eventManager->handle(Stubs\EventStub::class, new Stubs\EventStub());

	$debug = [
		0 => [
			'conditions' => [$condition::class => [false]],
			'listeners' => [Stubs\ListenerStub::class => [false]]
		],
		1 => [
			'conditions' => [$condition::class => [true]],
			'listeners' => [Stubs\ListenerStub::class => [true]]
		]
	];

	$this->assertSame($this->eventManager->getDebug(Stubs\EventStub::class), $debug);
});

test('EventManager - event stopped by listener exception', function () {
	// Wenn der erste Prozess aufgrund der Bedingung nicht ausgeführt wurde muss sichergestellt sein,
	// dass weitere Events trotzdem ausgeführt werden

	$this->repository->shouldReceive('forEvent')->andReturn(collect([$this->defaultProcess]));

	$listener = Mockery::mock(Stubs\ListenerStub::class);
	$listener->shouldReceive('bindManagedObject')->andReturnSelf();
	$listener->shouldReceive('handle')->once()->andThrow(new \RuntimeException());

	$this->container->instance(Stubs\ListenerStub::class, $listener);

	$this->eventManager->listen(Stubs\EventStub::class);

	$this->eventManager->handle(Stubs\EventStub::class, new Stubs\EventStub());

	$debug = [
		0 => [
			'conditions' => [Stubs\ConditionStub::class => [true]],
			'listeners' => [$listener::class => [false]]
		]
	];

	$this->assertSame($this->eventManager->getDebug(Stubs\EventStub::class), $debug);
});

test('EventManager - event stopped by listener exception, other processes will run successfully', function () {

	$this->repository->shouldReceive('forEvent')->andReturn(collect([
		$this->defaultProcess,
		clone $this->defaultProcess
	]));

	$listener = Mockery::mock(Stubs\ListenerStub::class);
	$listener->shouldReceive('bindManagedObject')->andReturnSelf();
	$listener->shouldReceive('handle')->once()->andThrow(new \RuntimeException());
	$listener->shouldReceive('handle')->once();

	$this->container->instance(Stubs\ListenerStub::class, $listener);

	$this->eventManager->listen(Stubs\EventStub::class);

	$this->eventManager->handle(Stubs\EventStub::class, new Stubs\EventStub());

	$debug = [
		0 => [
			'conditions' => [Stubs\ConditionStub::class => [true]],
			'listeners' => [$listener::class => [false]]
		],
		1 => [
			'conditions' => [Stubs\ConditionStub::class => [true]],
			'listeners' => [$listener::class => [true]]
		]
	];

	$this->assertSame($this->eventManager->getDebug(Stubs\EventStub::class), $debug);
});