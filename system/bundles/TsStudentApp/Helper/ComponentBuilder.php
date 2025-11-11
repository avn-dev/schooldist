<?php

namespace TsStudentApp\Helper;

use Carbon\Carbon;
use Core\Service\HtmlPurifier;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Ts\Dto\ExpectedPayment;
use Ts\Service\Inquiry\Scheduler\EventDto;
use TsStudentApp\Components;
use TsStudentApp\Properties\Property;
use TsStudentApp\Service\Util;

class ComponentBuilder
{
	public function __construct(private Container $container) {}

	public function Container(): Components\Container
	{
		return $this->container->make(Components\Container::class);
	}

	public function Card(string $title = null, string $subtitle = null): Components\Card
	{
		return $this->container->make(Components\Card::class)
			->title($title)
			->subtitle($subtitle);
	}

	public function MediaCard(string $imageType, int $imageId, string $title = null, string $subtitle = null): Components\MediaCard
	{
		return $this->container->make(Components\MediaCard::class)
			->image($imageType, $imageId)
			->title($title)
			->subtitle($subtitle);
	}

	public function HtmlBox(string $html, $tags = HtmlPurifier::SET_FRONTEND): Components\HtmlBox
	{
		return $this->container->make(Components\HtmlBox::class)
			->tags($tags)
			->content($html);
	}

	public function Grid(): Components\Grid
	{
		return $this->container->make(Components\Grid::class);
	}

	public function Col(): Components\Grid\Col
	{
		return $this->container->make(Components\Grid\Col::class);
	}

	public function Avatar(string $imageType, $imageId): Components\Avatar
	{
		return $this->container->make(Components\Avatar::class)
			->image(Util::imageUrl($imageType, $imageId));
	}

	public function List(): Components\ListContainer
	{
		return $this->container->make(Components\ListContainer::class);
	}

	public function Item(string $label): Components\Item
	{
		return $this->container->make(Components\Item::class)
			->label($label);
	}

	public function FileList(): Components\FileList
	{
		return $this->container->make(Components\FileList::class);
	}

	public function Heading(string $heading): Components\Heading
	{
		return $this->container->make(Components\Heading::class)
			->text($heading);
	}

	public function Slider(): Components\Slider
	{
		return $this->container->make(Components\Slider::class);
	}

	public function TeacherBox(\Ext_Thebing_Teacher $teacher): Components\TeacherBox
	{
		return $this->container->make(Components\TeacherBox::class)
			->teacher($teacher);
	}

	public function CourseBox(EventDto $event): Components\CourseBox
	{
		return $this->container->make(Components\CourseBox::class)
			->event($event);
	}

	public function DuePayment(Collection $terms): Components\DuePayment
	{
		return $this->container->make(Components\DuePayment::class)
			->terms($terms);
	}

	public function EventsSlider(Collection $events): Components\EventsSlider
	{
		return $this->container->make(Components\EventsSlider::class)
			->events($events);
	}

	public function ActivitiesSlider(Collection $activities): Components\ActivitiesSlider
	{
		return $this->container->make(Components\ActivitiesSlider::class)
			->activities($activities);
	}

	public function MessagesList(): Components\MessagesList
	{
		return $this->container->make(Components\MessagesList::class);
	}

}