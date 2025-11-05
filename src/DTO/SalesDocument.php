<?php

declare(strict_types=1);

namespace Mupy\TOConline\DTO;

final class SalesDocument
{
    /**
     * @param  SalesDocumentLine[]  $lines
     */
    public function __construct(
        public readonly int $id,
        public readonly string $documentNo,
        public readonly string $documentType,
        public readonly int $status,
        public readonly string $date,
        public readonly ?string $dueDate,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
        public readonly float $grossTotal,
        public readonly float $netTotal,
        public readonly float $taxPayable,
        public readonly float $pendingTotal,
        public readonly float $retentionValue,
        public readonly float $otherTaxesTotal,
        public readonly float $otherRetentions,
        public readonly ?string $currency,
        public readonly ?string $communicationStatus,
        public readonly ?string $documentArea,
        public readonly ?string $hashControl,
        public readonly ?string $documentHashSum,
        public readonly ?string $manualRegistrationSeries,
        public readonly ?string $manualRegistrationNumber,
        public readonly ?string $customerName,
        public readonly ?string $customerVat,
        public readonly ?string $customerAddress,
        public readonly ?string $customerPostcode,
        public readonly ?string $customerCity,
        public readonly ?string $customerCountry,
        public readonly ?string $shipmentAddress,
        public readonly ?string $shipmentPostcode,
        public readonly ?string $shipmentCity,
        public readonly ?string $operationCountry,
        public readonly bool $actsAsShipmentDocument,
        public readonly bool $isThirdParty,
        public readonly bool $isDocumentToSelf,
        public readonly ?string $vehicleRegistration,
        public readonly ?string $parentDocumentReference,
        public readonly ?string $reference,
        public readonly ?string $notes,
        public readonly ?string $manualRegistrationType,
        public readonly ?string $currencyIsoCode,
        public readonly ?float $currencyConversionRate,
        public readonly ?string $systemEntryDate,
        public readonly ?bool $emailed,
        public readonly ?bool $printed,
        public readonly bool $applyRetentionWhenPaid,
        public readonly float $retentionAwareGrossTotal,
        public readonly float $pendingRetentionValue,
        public readonly ?string $publicLink,
        public readonly ?string $communicationCodeSource,
        public readonly bool $cashedVat,
        public readonly bool $madeAvailableTo,
        public readonly ?string $voidedReason,
        public readonly ?string $retentionType,
        public readonly ?float $vatIncidenceRed,
        public readonly ?float $vatTotalRed,
        public readonly ?float $vatPercentageRed,
        public readonly array $lines,
        public readonly array $rawData = []
    ) {}

    public static function fromArray(array $data): self
    {
        $lines = [];
        if (! empty($data['lines']) && is_array($data['lines'])) {
            foreach ($data['lines'] as $line) {
                $lines[] = SalesDocumentLine::fromArray($line);
            }
        }

        return new self(
            id: (int) ($data['id'] ?? 0),
            documentNo: (string) ($data['document_no'] ?? ''),
            documentType: (string) ($data['document_type'] ?? ''),
            status: (int) ($data['status'] ?? 0),
            date: (string) ($data['date'] ?? ''),
            dueDate: $data['due_date'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            grossTotal: (float) ($data['gross_total'] ?? 0.0),
            netTotal: (float) ($data['net_total'] ?? 0.0),
            taxPayable: (float) ($data['tax_payable'] ?? 0.0),
            pendingTotal: (float) ($data['pending_total'] ?? 0.0),
            retentionValue: (float) ($data['retention_value'] ?? 0.0),
            otherTaxesTotal: (float) ($data['other_taxes_total'] ?? 0.0),
            otherRetentions: (float) ($data['other_retentions'] ?? 0.0),
            currency: $data['currency'] ?? $data['currency_iso_code'] ?? null,
            communicationStatus: $data['communication_status'] ?? null,
            documentArea: $data['document_area'] ?? null,
            hashControl: $data['hash_control'] ?? null,
            documentHashSum: $data['document_hash_sum'] ?? null,
            manualRegistrationSeries: $data['manual_registration_series'] ?? null,
            manualRegistrationNumber: $data['manual_registration_number'] ?? null,
            customerName: $data['customer_business_name'] ?? null,
            customerVat: $data['customer_tax_registration_number'] ?? null,
            customerAddress: $data['customer_address_detail'] ?? null,
            customerPostcode: $data['customer_postcode'] ?? null,
            customerCity: $data['customer_city'] ?? null,
            customerCountry: $data['customer_country'] ?? null,
            shipmentAddress: $data['shipment_address_detail'] ?? null,
            shipmentPostcode: $data['shipment_postcode'] ?? null,
            shipmentCity: $data['shipment_city'] ?? null,
            operationCountry: $data['operation_country'] ?? null,
            actsAsShipmentDocument: (bool) ($data['acts_as_shipment_document'] ?? false),
            isThirdParty: (bool) ($data['is_third_party'] ?? false),
            isDocumentToSelf: (bool) ($data['is_document_to_self'] ?? false),
            vehicleRegistration: $data['vehicle_registration'] ?? null,
            parentDocumentReference: $data['parent_document_reference'] ?? null,
            reference: $data['reference'] ?? null,
            notes: $data['notes'] ?? null,
            manualRegistrationType: $data['manual_registration_type'] ?? null,
            currencyIsoCode: $data['currency_iso_code'] ?? null,
            currencyConversionRate: isset($data['currency_conversion_rate']) ? (float) $data['currency_conversion_rate'] : null,
            systemEntryDate: $data['system_entry_date'] ?? null,
            emailed: isset($data['emailed']) ? (bool) $data['emailed'] : null,
            printed: isset($data['printed']) ? (bool) $data['printed'] : null,
            applyRetentionWhenPaid: (bool) ($data['apply_retention_when_paid'] ?? false),
            retentionAwareGrossTotal: (float) ($data['retention_aware_gross_total'] ?? 0.0),
            pendingRetentionValue: (float) ($data['pending_retention_value'] ?? 0.0),
            publicLink: $data['public_link'] ?? null,
            communicationCodeSource: $data['communication_code_source'] ?? null,
            cashedVat: (bool) ($data['cashed_vat'] ?? false),
            madeAvailableTo: (bool) ($data['made_available_to'] ?? false),
            voidedReason: $data['voided_reason'] ?? null,
            retentionType: $data['retention_type'] ?? null,
            vatIncidenceRed: isset($data['vat_incidence_red']) ? (float) $data['vat_incidence_red'] : null,
            vatTotalRed: isset($data['vat_total_red']) ? (float) $data['vat_total_red'] : null,
            vatPercentageRed: isset($data['vat_percentage_red']) ? (float) $data['vat_percentage_red'] : null,
            lines: $lines,
            rawData: $data
        );
    }
}
