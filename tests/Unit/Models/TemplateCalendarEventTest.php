<?php

namespace T1k3\LaravelCalendarEvent\Tests\Unit\Models;


use Carbon\Carbon;
use T1k3\LaravelCalendarEvent\Enums\RecurringFrequenceType;
use T1k3\LaravelCalendarEvent\Models\CalendarEvent;
use T1k3\LaravelCalendarEvent\Models\TemplateCalendarEvent;
use T1k3\LaravelCalendarEvent\Tests\Fixtures\Models\Place;
use T1k3\LaravelCalendarEvent\Tests\Fixtures\Models\User;
use T1k3\LaravelCalendarEvent\Tests\TestCase;

/**
 * Class TemplateCalendarEventTest
 * @package T1k3\LaravelCalendarEvent\Tests\Unit\Models
 */
class TemplateCalendarEventTest extends TestCase
{
    /**
     * @var  TemplateCalendarEvent $templateCalendarEvent
     */
    private $templateCalendarEvent;

    /**
     * @var CalendarEvent $calendarEvent
     */
    private $calendarEvent;

    /**
     * Setup
     */
    protected function setUp()
    {
        parent::setUp();
        $this->templateCalendarEvent = new TemplateCalendarEvent();
        $this->calendarEvent         = new CalendarEvent();
    }

    /**
     * Check instance
     * @test
     */
    public function createInstanceFromClass()
    {
        $this->assertInstanceOf(TemplateCalendarEvent::class, $this->templateCalendarEvent);
    }

    /**
     * Check fillables
     * @test
     */
    public function getFillable()
    {
        $expectedFillables = [
            'title',
            'start_date',
            'start_time',
            'end_date',
            'end_time',
            'description',
            'is_recurring',
            'end_of_recurring',
            'frequence_number_of_recurring',
            'frequence_type_of_recurring',
            'is_public',
        ];

        $this->assertArraySubset($expectedFillables, $this->templateCalendarEvent->getFillable());
    }

    /**
     * Check relation with calendar_events
     * @test
     */
    public function events()
    {
        $templateCalendarEvent = factory(TemplateCalendarEvent::class)->create();
        $calendarEvent         = factory(CalendarEvent::class)->make();
        $calendarEvent->template()->associate($templateCalendarEvent);
        $calendarEvent->save();

        $this->assertInstanceOf(CalendarEvent::class, $templateCalendarEvent->events()->first());
    }

    /**
     * Check parent (template_calendar_events)
     * @test
     */
    public function parent()
    {
        $templateCalendarEvent      = factory(TemplateCalendarEvent::class)->create();
        $templateCalendarEventChild = factory(TemplateCalendarEvent::class)->create();
        $templateCalendarEventChild->parent()->associate($templateCalendarEvent);

        $this->assertInstanceOf(TemplateCalendarEvent::class, $templateCalendarEventChild->parent);
    }

    /**
     * Check user relation with null
     * @test
     */
    public function user_null()
    {
        $this->app['config']->set('calendar-event.user.model', null);
        $templateCalendarEvent = factory(TemplateCalendarEvent::class)->create();
        $user                  = $templateCalendarEvent->user();

        $this->assertNull($user);
    }

    /**
     * Check user relation
     * @test
     */
    public function user()
    {
        $this->app['config']->set('calendar-event.user.model', User::class);
        $user                 = factory(config('calendar-event.user.model'))->create();
        $templateCalendarEvent = factory(TemplateCalendarEvent::class)->create();
        $templateCalendarEvent->user()->associate($user);

        $this->assertInstanceOf(User::class, $user);
        $this->assertInstanceOf(User::class, $templateCalendarEvent->user);
    }

    /**
     * Check place relation with null
     * @test
     */
    public function place_null()
    {
        $this->app['config']->set('calendar-event.place.model', null);
        $templateCalendarEvent = factory(TemplateCalendarEvent::class)->create();
        $place                 = $templateCalendarEvent->place();

        $this->assertNull($place);
    }

    /**
     * Check place relation
     * @test
     */
    public function place()
    {
        $this->app['config']->set('calendar-event.place.model', Place::class);
        $place                 = factory(config('calendar-event.place.model'))->create();
        $templateCalendarEvent = factory(TemplateCalendarEvent::class)->create();
        $templateCalendarEvent->place()->associate($place);

        $this->assertInstanceOf(Place::class, $place);
        $this->assertInstanceOf(Place::class, $templateCalendarEvent->place);
    }

    /**
     * @test
     */
    public function scopeRecurring()
    {
        factory(TemplateCalendarEvent::class)->create([
            'title'        => str_random(16),
            'start_date'   => Carbon::parse('2017-08-29'),
            'start_time'   => Carbon::parse('10:00'),
            'end_date'     => Carbon::parse('2017-08-29'),
            'end_time'     => Carbon::parse('11:00'),
            'description'  => str_random(32),
            'is_recurring' => false,
            'is_public'    => true,
        ]);
        factory(TemplateCalendarEvent::class)->create([
            'title'                         => str_random(16),
            'start_date'                    => Carbon::parse('2017-08-29'),
            'start_time'                    => Carbon::parse('10:00'),
            'end_date'                      => Carbon::parse('2017-08-29'),
            'end_time'                      => Carbon::parse('11:00'),
            'description'                   => str_random(32),
            'is_recurring'                  => true,
            'frequence_number_of_recurring' => 1,
            'frequence_type_of_recurring'   => RecurringFrequenceType::WEEK,
            'is_public'                     => true,
        ]);

        $templateCalendarEvents = $this->templateCalendarEvent->recurring()->get();

        $this->assertEquals(1, $templateCalendarEvents->count());
        $this->assertEquals(true, $templateCalendarEvents->first()->is_recurring);
    }

    /**
     * @test
     */
    public function scopePublic()
    {
        $input = [
            'title'        => str_random(16),
            'start_date'   => Carbon::parse('2017-08-29'),
            'start_time'   => Carbon::parse('10:00'),
            'end_date'     => Carbon::parse('2017-08-29'),
            'end_time'     => Carbon::parse('11:00'),
            'description'  => str_random(32),
            'is_recurring' => false,
            'is_public'    => true,
        ];

        factory(TemplateCalendarEvent::class)->create(array_merge($input, ['is_public' => false]));
        factory(TemplateCalendarEvent::class)->create($input);
        factory(TemplateCalendarEvent::class)->create($input);

        $this->assertEquals(2, $this->templateCalendarEvent->public()->count());
    }

    public function dataProvider_for_getNextCalendarEventStartDate()
    {
        return [
            [
                [
                    'title'        => str_random(16),
                    'start_date'   => Carbon::parse('2017-08-30'),
                    'start_time'   => Carbon::parse('10:00'),
                    'end_date'     => Carbon::parse('2017-08-30'),
                    'end_time'     => Carbon::parse('11:00'),
                    'description'  => 'Foo bar',
                    'is_recurring' => false,
                ],
                null,
            ],
            [
                [
                    'title'                         => str_random(16),
                    'start_date'                    => Carbon::parse('2017-08-30'),
                    'start_time'                    => Carbon::parse('10:00'),
                    'end_date'                      => Carbon::parse('2017-08-30'),
                    'end_time'                      => Carbon::parse('11:00'),
                    'description'                   => 'Foo bar',
                    'is_recurring'                  => true,
                    'frequence_number_of_recurring' => 1,
                    'frequence_type_of_recurring'   => RecurringFrequenceType::DAY,
                    'is_public'                     => true,
                ],
                Carbon::parse('2017-08-30')->addDay(),
            ],
            [
                [
                    'title'                         => str_random(16),
                    'start_date'                    => Carbon::parse('2017-08-30'),
                    'start_time'                    => Carbon::parse('10:00'),
                    'end_date'                      => Carbon::parse('2017-08-30'),
                    'end_time'                      => Carbon::parse('11:00'),
                    'description'                   => 'Foo bar',
                    'is_recurring'                  => true,
                    'frequence_number_of_recurring' => 1,
                    'frequence_type_of_recurring'   => RecurringFrequenceType::WEEK,
                    'is_public'                     => true,
                    'end_of_recurring'              => Carbon::parse('2017-08-30'),
                ],
                null,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataProvider_for_getNextCalendarEventStartDate
     * @param $input
     * @param $result
     */
    public function getNextCalendarEventStartDate($input, $result)
    {
        $calendarEvent = $this->calendarEvent->createCalendarEvent($input);
        $startDate     = $calendarEvent->template->getNextCalendarEventStartDate(Carbon::parse($input['start_date']));

        $this->assertEquals($result, $startDate);
    }

    /**
     * Data provider for generate next calendar event
     * @return array
     */
    public function dataProvider_for_generateNextCalendarEvent()
    {
        return [
            [Carbon::parse('2017-08-10'), 4, RecurringFrequenceType::DAY, Carbon::parse('2017-08-14')],
            [Carbon::parse('2017-08-10'), 2, RecurringFrequenceType::WEEK, Carbon::parse('2017-08-24')],
            [Carbon::parse('2017-08-10'), 3, RecurringFrequenceType::MONTH, Carbon::parse('2017-11-10')],
            [Carbon::parse('2017-08-10'), 1, RecurringFrequenceType::YEAR, Carbon::parse('2018-08-10')],
        ];
    }

    /**
     * @test
     * @dataProvider dataProvider_for_generateNextCalendarEvent
     * @param $startDate
     * @param $frequence_number_of_recurring
     * @param $frequence_type_of_recurring
     * @param $calendarEventNext_startDate
     */
    public function generateNextCalendarEvent($startDate, $frequence_number_of_recurring, $frequence_type_of_recurring, $calendarEventNext_startDate)
    {
        $templateCalendarEvent = factory(TemplateCalendarEvent::class)->create([
            'title'                         => str_random(16),
            'start_date'                    => $startDate,
            'start_time'                    => Carbon::parse('10:00'),
            'end_date'                      => $startDate,
            'end_time'                      => Carbon::parse('11:00'),
            'description'                   => str_random(32),
            'is_recurring'                  => true,
            'frequence_number_of_recurring' => $frequence_number_of_recurring,
            'frequence_type_of_recurring'   => $frequence_type_of_recurring,
            'is_public'                     => true,
        ]);
        $calendarEvent         = factory(CalendarEvent::class)->make(['start_date' => $startDate]);
        $calendarEvent->template()->associate($templateCalendarEvent);
        $calendarEvent->save();

        $calendarEventNext = $templateCalendarEvent->generateNextCalendarEvent($startDate);

        $this->assertInstanceOf(CalendarEvent::class, $calendarEventNext);
        $this->assertInstanceOf(TemplateCalendarEvent::class, $calendarEventNext->template);
        $this->assertEquals($calendarEvent->template->id, $calendarEventNext->template->id);
        $this->assertEquals($calendarEventNext_startDate, $calendarEventNext->start_date);

        $this->assertDatabaseHas('calendar_events', ['start_date' => $calendarEventNext_startDate]);
    }

    /**
     * @test
     */
    public function createCalendarEvent()
    {
        $templateCalendarEvent = factory(TemplateCalendarEvent::class)->create();
        $startDate             = Carbon::parse('2017-08-29');
        $calendarEvent         = $templateCalendarEvent->createCalendarEvent($startDate);

        $this->assertInstanceOf(CalendarEvent::class, $calendarEvent);
        $this->assertInstanceOf(CalendarEvent::class, $templateCalendarEvent->events->first());
        $this->assertEquals($templateCalendarEvent->id, $calendarEvent->template_calendar_event_id);
        $this->assertEquals($startDate, $calendarEvent->start_date);

        $this->assertDatabaseHas('calendar_events', ['start_date' => $startDate]);
    }

    /**
     * @test
     */
    public function updateCalendarEvent()
    {
        $inputCreate           = [
            'title'                         => str_random(16),
            'start_date'                    => Carbon::parse('2017-08-30'),
            'start_time'                    => Carbon::parse('10:00'),
            'end_date'                      => Carbon::parse('2017-08-30'),
            'end_time'                      => Carbon::parse('11:00'),
            'description'                   => str_random(16),
            'is_recurring'                  => true,
            'frequence_number_of_recurring' => 1,
            'frequence_type_of_recurring'   => RecurringFrequenceType::WEEK,
            'is_public'                     => true,
        ];
        $templateCalendarEvent = factory(TemplateCalendarEvent::class)->create($inputCreate);
        $calendarEvent         = $templateCalendarEvent->createCalendarEvent(Carbon::parse($inputCreate['start_date']));
        $startDateNext         = $calendarEvent->start_date->addWeek();
        $calendarEventUpdated  = $templateCalendarEvent->updateCalendarEvent($startDateNext, $inputCreate);

        $this->assertNotEquals($templateCalendarEvent->id, $calendarEventUpdated->template->id);
        $this->assertDatabaseHas('template_calendar_events', $inputCreate + ['id' => $calendarEventUpdated->template->id]);
    }

    /**
     * @test
     */
    public function editCalendarEvent_notExistCalendarEvent()
    {
        $inputCreate = [
            'title'                         => str_random(16),
            'start_date'                    => Carbon::parse('2017-08-30'),
            'start_time'                    => Carbon::parse('10:00'),
            'end_date'                      => Carbon::parse('2017-08-30'),
            'end_time'                      => Carbon::parse('11:00'),
            'description'                   => str_random(16),
            'is_recurring'                  => true,
            'frequence_number_of_recurring' => 1,
            'frequence_type_of_recurring'   => RecurringFrequenceType::WEEK,
            'is_public'                     => true,
        ];
        $inputUpdate = [
            'description' => str_random(16),
        ];

        $templateCalendarEvent = factory(TemplateCalendarEvent::class)->create($inputCreate);
        $calendarEvent         = $templateCalendarEvent->createCalendarEvent(Carbon::parse($inputCreate['start_date']));
        $startDateNext         = $calendarEvent->start_date->addWeek();
        $calendarEventUpdated  = $templateCalendarEvent->editCalendarEvent($startDateNext, $inputUpdate);

        $this->assertInstanceOf(CalendarEvent::class, $calendarEvent);
        $this->assertEquals($templateCalendarEvent->id, $calendarEventUpdated->template->parent_id);
        $this->assertEquals($templateCalendarEvent->fresh()->end_of_recurring, $startDateNext);

        $this->assertDatabaseHas('template_calendar_events', $inputUpdate);
    }

    /**
     * @test
     */
    public function editCalendarEvent_existCalendarEvent()
    {
        $inputCreate = [
            'title'                         => str_random(16),
            'start_date'                    => Carbon::parse('2017-08-30'),
            'start_time'                    => Carbon::parse('10:00'),
            'end_date'                      => Carbon::parse('2017-08-30'),
            'end_time'                      => Carbon::parse('11:00'),
            'description'                   => 'Foo bar',
            'is_recurring'                  => true,
            'frequence_number_of_recurring' => 1,
            'frequence_type_of_recurring'   => RecurringFrequenceType::WEEK,
            'is_public'                     => true,
        ];
        $inputUpdate = [
            'description' => str_random(16),
        ];

        $templateCalendarEvent = factory(TemplateCalendarEvent::class)->create($inputCreate);
        $calendarEvent         = $templateCalendarEvent->createCalendarEvent(Carbon::parse($inputCreate['start_date']));
        $calendarEventUpdated  = $templateCalendarEvent->editCalendarEvent($calendarEvent->start_date, $inputUpdate);

        $this->assertNotNull($calendarEvent->fresh()->deleted_at);
        $this->assertInstanceOf(CalendarEvent::class, $calendarEvent);
        $this->assertEquals($templateCalendarEvent->id, $calendarEventUpdated->template->parent_id);
        $this->assertEquals($templateCalendarEvent->fresh()->end_of_recurring, $calendarEvent->start_date);

        $this->assertDatabaseHas('template_calendar_events', $inputUpdate);
    }

    /**
     * Data provider for generateNextCalendarEvent_notGenerated
     * @return array
     */
    public function dataProvider_for_generateNextCalendarEvent_notGenerated()
    {
        return [
            [
                [
                    'title'                         => str_random(16),
                    'start_date'                    => Carbon::parse('2017-08-29'),
                    'start_time'                    => Carbon::parse('10:00'),
                    'end_date'                      => Carbon::parse('2017-08-29'),
                    'end_time'                      => Carbon::parse('11:00'),
                    'description'                   => str_random(32),
                    'is_recurring'                  => true,
                    'frequence_number_of_recurring' => 1,
                    'frequence_type_of_recurring'   => RecurringFrequenceType::WEEK,
                    'is_public'                     => true,
                    'end_of_recurring'              => '2017-09-04',
                ],
            ],
            [
                [
                    'title'        => str_random(16),
                    'start_date'   => Carbon::parse('2017-08-29'),
                    'start_time'   => Carbon::parse('10:00'),
                    'end_date'     => Carbon::parse('2017-08-29'),
                    'end_time'     => Carbon::parse('11:00'),
                    'description'  => str_random(32),
                    'is_recurring' => false,
                    'is_public'    => true,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataProvider_for_generateNextCalendarEvent_notGenerated
     */
    public function generateNextCalendarEvent_notGenerated(array $input)
    {
        $templateCalendarEvent = factory(TemplateCalendarEvent::class)->create($input);
        $calendarEvent         = factory(CalendarEvent::class)->make(['start_date' => $input['start_date']]);
        $calendarEvent->template()->associate($templateCalendarEvent);
        $calendarEvent->save();

        $calendarEventNext = $templateCalendarEvent->generateNextCalendarEvent(Carbon::parse($input['start_date']));

        $this->assertNull($calendarEventNext);
    }

    /**
     * @test
     */
    public function deleteCalendarEvent_notExist()
    {
        $inputCreate           = [
            'title'                         => str_random(16),
            'start_date'                    => Carbon::parse('2017-08-25'),
            'start_time'                    => Carbon::parse('16:00'),
            'end_date'                      => Carbon::parse('2017-08-25'),
            'end_time'                      => Carbon::parse('17:00'),
            'description'                   => str_random(32),
            'is_recurring'                  => true,
            'frequence_number_of_recurring' => 1,
            'frequence_type_of_recurring'   => RecurringFrequenceType::WEEK,
            'is_public'                     => true,
        ];
        $calendarEvent         = $this->calendarEvent->createCalendarEvent($inputCreate);
        $templateCalendarEvent = $calendarEvent->template;

        $startDate = Carbon::parse('2017-09-08');
        $isDeleted = $templateCalendarEvent->deleteCalendarEvent($startDate);

        $this->assertTrue($isDeleted);
        $this->assertNull($calendarEvent->deleted_at);
        $this->assertNull($templateCalendarEvent->deleted_at);
        $this->assertEquals($startDate, $templateCalendarEvent->end_of_recurring);
    }

    /**
     * @test
     */
    public function deleteCalendarEvent_exist()
    {
        $startDate   = Carbon::parse('2017-08-25');
        $inputCreate = [
            'title'                         => str_random(16),
            'start_date'                    => $startDate,
            'start_time'                    => Carbon::parse('16:00'),
            'end_date'                      => $startDate,
            'end_time'                      => Carbon::parse('17:00'),
            'description'                   => str_random(32),
            'is_recurring'                  => true,
            'frequence_number_of_recurring' => 1,
            'frequence_type_of_recurring'   => RecurringFrequenceType::WEEK,
            'is_public'                     => true,
        ];

        $calendarEvent         = $this->calendarEvent->createCalendarEvent($inputCreate);
        $templateCalendarEvent = $calendarEvent->template;
        $isDeleted             = $templateCalendarEvent->deleteCalendarEvent($startDate);

        $this->assertTrue($isDeleted);
        $this->assertNotNull($calendarEvent->fresh()->deleted_at);
        $this->assertNotNull($templateCalendarEvent->fresh()->deleted_at);
        $this->assertEquals($startDate, $templateCalendarEvent->fresh()->end_of_recurring);
    }
}
