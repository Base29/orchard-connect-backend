<?php

namespace Tests\Feature;

use App\Models\Poll;
use App\Models\User;
use App\Filament\Resources\Polls\PollResource;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentPollTest extends TestCase
{
    use RefreshDatabase;

    public function test_poll_resource_form_has_options_repeater(): void
    {
        $schema = new Schema();
        $schema->model(Poll::class);
        $schema->record(new Poll());
        
        $form = PollResource::form($schema);

        $components = $form->getComponents();

        // Find the options repeater component
        $optionsRepeater = null;
        foreach ($components as $component) {
            if ($component instanceof Repeater && $component->getName() === 'options') {
                $optionsRepeater = $component;
                break;
            }
        }

        $this->assertNotNull($optionsRepeater, 'Form schema does not contain options repeater component.');
        $this->assertEquals('options', $optionsRepeater->getRelationshipName());
        $this->assertEquals(2, $optionsRepeater->getMinItems());
        $this->assertEquals(10, $optionsRepeater->getMaxItems());
    }
}
