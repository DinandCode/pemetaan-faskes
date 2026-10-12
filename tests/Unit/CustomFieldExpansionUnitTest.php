<?php

namespace Tests\Unit;

use App\Http\Controllers\FaskesFieldController;
use App\Models\FaskesFieldDefinition;
use App\Models\FaskesFieldValue;
use PHPUnit\Framework\TestCase;

class CustomFieldExpansionUnitTest extends TestCase
{
    public function test_valid_types_contains_all_10_required_types(): void
    {
        $expected = [
            'text',
            'textarea',
            'number',
            'date',
            'boolean',
            'select',
            'multiselect',
            'url',
            'email',
            'phone',
        ];

        $this->assertEquals($expected, FaskesFieldDefinition::VALID_TYPES);
    }

    public function test_get_config_array_returns_correct_defaults_per_type(): void
    {
        // 1. Text
        $defText = new FaskesFieldDefinition(['type' => 'text', 'config' => null]);
        $cfgText = $defText->getConfigArray();
        $this->assertEquals(255, $cfgText['max_length']);
        $this->assertSame('', $cfgText['placeholder']);

        // 2. Textarea
        $defTextarea = new FaskesFieldDefinition(['type' => 'textarea', 'config' => ['rows' => 5]]);
        $cfgTextarea = $defTextarea->getConfigArray();
        $this->assertEquals(2000, $cfgTextarea['max_length']);
        $this->assertEquals(5, $cfgTextarea['rows']);

        // 3. Number
        $defNum = new FaskesFieldDefinition(['type' => 'number', 'config' => ['decimals' => 2, 'unit' => 'm²']]);
        $cfgNum = $defNum->getConfigArray();
        $this->assertEquals(2, $cfgNum['decimals']);
        $this->assertEquals('m²', $cfgNum['unit']);

        // 4. Multiselect
        $defMulti = new FaskesFieldDefinition(['type' => 'multiselect', 'config' => ['max_select' => 3]]);
        $cfgMulti = $defMulti->getConfigArray();
        $this->assertEquals(3, $cfgMulti['max_select']);
    }

    public function test_formatted_value_accessor_formatting(): void
    {
        // 1. Number dengan desimal dan unit
        $defNumber = new FaskesFieldDefinition([
            'type' => 'number',
            'config' => ['decimals' => 2, 'unit' => 'm²'],
        ]);
        $valNumber = new FaskesFieldValue([
            'value' => '1250.50',
            'value_number' => 1250.50,
        ]);
        $valNumber->setRelation('definition', $defNumber);
        $this->assertEquals('1.250,50 m²', $valNumber->formatted_value);

        // 2. Boolean Ya / Tidak
        $defBool = new FaskesFieldDefinition(['type' => 'boolean']);
        $valBoolYa = new FaskesFieldValue(['value' => 'Ya', 'value_bool' => true]);
        $valBoolYa->setRelation('definition', $defBool);
        $this->assertEquals('Ya', $valBoolYa->formatted_value);

        $valBoolTidak = new FaskesFieldValue(['value' => 'Tidak', 'value_bool' => false]);
        $valBoolTidak->setRelation('definition', $defBool);
        $this->assertEquals('Tidak', $valBoolTidak->formatted_value);

        // 3. Multiselect
        $defMulti = new FaskesFieldDefinition(['type' => 'multiselect']);
        $valMulti = new FaskesFieldValue([
            'value' => 'ATM; Kantin; Wi-Fi',
            'value_json' => ['ATM', 'Kantin', 'Wi-Fi'],
        ]);
        $valMulti->setRelation('definition', $defMulti);
        $this->assertEquals('ATM, Kantin, Wi-Fi', $valMulti->formatted_value);
    }
}
