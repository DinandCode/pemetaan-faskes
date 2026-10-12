<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Support\ExcelSecurityHelper;
use App\Support\CustomFieldHelper;
use App\Models\FaskesFieldDefinition;

class ImportExportUnitTest extends TestCase
{
    /** @test */
    public function it_sanitizes_csv_excel_formula_injection()
    {
        // Dangerous formula triggers: =, +, -, @, tab, newline
        $this->assertEquals("'=SUM(A1:A10)", ExcelSecurityHelper::sanitizeCellValue('=SUM(A1:A10)'));
        $this->assertEquals("'+12345", ExcelSecurityHelper::sanitizeCellValue('+12345'));
        $this->assertEquals("'-12345", ExcelSecurityHelper::sanitizeCellValue('-12345'));
        $this->assertEquals("'@SUM", ExcelSecurityHelper::sanitizeCellValue('@SUM'));
        $this->assertEquals("'\tcalc", ExcelSecurityHelper::sanitizeCellValue("\tcalc"));
        $this->assertEquals("'=cmd|' /C calc'!A0", ExcelSecurityHelper::sanitizeCellValue("=cmd|' /C calc'!A0"));

        // Safe values should remain unchanged
        $this->assertEquals("Puskesmas Purwokerto", ExcelSecurityHelper::sanitizeCellValue("Puskesmas Purwokerto"));
        $this->assertEquals(123, ExcelSecurityHelper::sanitizeCellValue(123));
        $this->assertNull(ExcelSecurityHelper::sanitizeCellValue(null));
    }

    /** @test */
    public function it_processes_all_ten_custom_field_types_correctly()
    {
        // 1. TEXT
        $defText = new FaskesFieldDefinition(['type' => 'text', 'label' => 'Kode RS', 'config' => ['max_length' => 10]]);
        $res = CustomFieldHelper::processValue($defText, 'ABC-123');
        $this->assertNull($res['error']);
        $this->assertEquals('ABC-123', $res['value']);

        // 2. TEXTAREA
        $defArea = new FaskesFieldDefinition(['type' => 'textarea', 'label' => 'Catatan', 'config' => ['max_length' => 100]]);
        $res = CustomFieldHelper::processValue($defArea, 'Ini catatan penting');
        $this->assertNull($res['error']);
        $this->assertEquals('Ini catatan penting', $res['value']);

        // 3. NUMBER (Integer & Decimal)
        $defNum = new FaskesFieldDefinition(['type' => 'number', 'label' => 'Luas', 'config' => ['min' => 0, 'max' => 1000, 'decimals' => 2, 'unit' => 'm²']]);
        $res = CustomFieldHelper::processValue($defNum, '150,75');
        $this->assertNull($res['error']);
        $this->assertEquals('150.75', $res['value']);
        $this->assertEquals(150.75, $res['value_number']);

        // 4. DATE
        $defDate = new FaskesFieldDefinition(['type' => 'date', 'label' => 'Berdiri']);
        $res = CustomFieldHelper::processValue($defDate, '2024-05-15');
        $this->assertNull($res['error']);
        $this->assertEquals('2024-05-15', $res['value_date']);

        // 5. BOOLEAN
        $defBool = new FaskesFieldDefinition(['type' => 'boolean', 'label' => 'MOU']);
        $res = CustomFieldHelper::processValue($defBool, 'Ya');
        $this->assertNull($res['error']);
        $this->assertTrue($res['value_bool']);
        $this->assertEquals('Ya', $res['value']);

        // 6. SELECT
        $defSelect = new FaskesFieldDefinition(['type' => 'select', 'label' => 'Tingkat', 'options' => ['A', 'B', 'C']]);
        $res = CustomFieldHelper::processValue($defSelect, 'B');
        $this->assertNull($res['error']);
        $this->assertEquals('B', $res['value']);

        $resInvalid = CustomFieldHelper::processValue($defSelect, 'Z');
        $this->assertNotNull($resInvalid['error']);

        // 7. MULTISELECT
        $defMulti = new FaskesFieldDefinition(['type' => 'multiselect', 'label' => 'Fasilitas', 'options' => ['Lab', 'Apotek', 'UGD']]);
        $res = CustomFieldHelper::processValue($defMulti, 'Lab; Apotek');
        $this->assertNull($res['error']);
        $this->assertEquals(['Lab', 'Apotek'], $res['value_json']);

        // 8. URL
        $defUrl = new FaskesFieldDefinition(['type' => 'url', 'label' => 'Website']);
        $res = CustomFieldHelper::processValue($defUrl, 'https://dinkes.banyumaskab.go.id');
        $this->assertNull($res['error']);
        $this->assertEquals('https://dinkes.banyumaskab.go.id', $res['value']);

        $resBadUrl = CustomFieldHelper::processValue($defUrl, 'ftp://invalid-url');
        $this->assertNotNull($resBadUrl['error']);

        // 9. EMAIL
        $defEmail = new FaskesFieldDefinition(['type' => 'email', 'label' => 'Email']);
        $res = CustomFieldHelper::processValue($defEmail, 'admin@dinkes.go.id');
        $this->assertNull($res['error']);
        $this->assertEquals('admin@dinkes.go.id', $res['value']);

        // 10. PHONE
        $defPhone = new FaskesFieldDefinition(['type' => 'phone', 'label' => 'Hotline']);
        $res = CustomFieldHelper::processValue($defPhone, '+62 812-3456-7890');
        $this->assertNull($res['error']);
        $this->assertEquals('+62 812-3456-7890', $res['value']);
    }
}
