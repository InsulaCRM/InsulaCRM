<?php

namespace Tests\Unit\Services;

use App\Services\AddressNormalizationService;
use PHPUnit\Framework\TestCase;

class AddressNormalizationServiceTest extends TestCase
{
    public function test_normalizes_street_abbreviations(): void
    {
        $this->assertEquals('123 Main Street', AddressNormalizationService::normalize('123 Main St'));
        $this->assertEquals('456 Oak Avenue', AddressNormalizationService::normalize('456 Oak Ave'));
        $this->assertEquals('789 Pine Boulevard', AddressNormalizationService::normalize('789 Pine Blvd'));
        $this->assertEquals('100 Elm Drive', AddressNormalizationService::normalize('100 Elm Dr'));
        $this->assertEquals('200 Cedar Lane', AddressNormalizationService::normalize('200 Cedar Ln'));
        $this->assertEquals('300 Birch Court', AddressNormalizationService::normalize('300 Birch Ct'));
        $this->assertEquals('400 Maple Road', AddressNormalizationService::normalize('400 Maple Rd'));
    }

    public function test_normalizes_directionals(): void
    {
        $this->assertEquals('N Main Street', AddressNormalizationService::normalize('n Main St'));
        $this->assertEquals('S Broadway Avenue', AddressNormalizationService::normalize('s Broadway Ave'));
        $this->assertEquals('NE 5th Street', AddressNormalizationService::normalize('ne 5th St'));
    }

    public function test_normalizes_unit_types(): void
    {
        $this->assertEquals('123 Main Street Apt 4', AddressNormalizationService::normalize('123 Main St apartment 4'));
        $this->assertEquals('456 Oak Avenue Ste 200', AddressNormalizationService::normalize('456 Oak Ave suite 200'));
    }

    public function test_removes_periods_from_abbreviations(): void
    {
        $this->assertEquals('123 Main Street', AddressNormalizationService::normalize('123 Main St.'));
    }

    public function test_removes_extra_whitespace(): void
    {
        $this->assertEquals('123 Main Street', AddressNormalizationService::normalize('123  Main   St'));
    }

    public function test_normalizes_city_to_title_case(): void
    {
        $this->assertEquals('New York', AddressNormalizationService::normalizeCity('new york'));
        $this->assertEquals('San Francisco', AddressNormalizationService::normalizeCity('SAN FRANCISCO'));
        $this->assertEquals('Los Angeles', AddressNormalizationService::normalizeCity('los angeles'));
    }

    public function test_normalizes_state_to_uppercase(): void
    {
        $this->assertEquals('FL', AddressNormalizationService::normalizeState('fl'));
        $this->assertEquals('NY', AddressNormalizationService::normalizeState('  ny  '));
        $this->assertEquals('CA', AddressNormalizationService::normalizeState('Ca'));
    }

    public function test_normalizes_zip_code(): void
    {
        $this->assertEquals('33101', AddressNormalizationService::normalizeZipCode('33101'));
        $this->assertEquals('33101-1234', AddressNormalizationService::normalizeZipCode('33101 - 1234'));
        $this->assertEquals('33101', AddressNormalizationService::normalizeZipCode('  33101  '));
    }

    public function test_normalize_all_processes_all_fields(): void
    {
        $data = [
            'address' => '123 Main St',
            'city' => 'new york',
            'state' => 'ny',
            'zip_code' => '10001',
        ];

        $normalized = AddressNormalizationService::normalizeAll($data);

        $this->assertEquals('123 Main Street', $normalized['address']);
        $this->assertEquals('New York', $normalized['city']);
        $this->assertEquals('NY', $normalized['state']);
        $this->assertEquals('10001', $normalized['zip_code']);
    }

    public function test_normalize_all_skips_empty_fields(): void
    {
        $data = ['address' => '', 'city' => null];

        $normalized = AddressNormalizationService::normalizeAll($data);

        $this->assertEquals('', $normalized['address']);
        $this->assertNull($normalized['city']);
    }
}
