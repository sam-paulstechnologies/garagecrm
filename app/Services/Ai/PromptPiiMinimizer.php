<?php

namespace App\Services\Ai;

/**
 * Minimizes personally identifiable information (PII) in free text before it is
 * placed into a prompt sent to a third-party model (OpenAI).
 *
 * The goal is to keep the parts of a message that are needed for automotive
 * intent/entity analysis (vehicle make/model/year, service words, dates, time
 * windows, plate/VIN-like service tokens) while stripping direct identifiers
 * that the model never needs to classify a message: phone numbers, email
 * addresses, and long free-floating digit runs (which are usually account /
 * card / order numbers rather than anything service-relevant).
 *
 * Rules are deliberately conservative: false positives (redacting a real
 * vehicle token) are worse than a rare missed identifier, because the model's
 * job is classification, not identity resolution. This class is pure and has no
 * dependencies so it is trivial to unit test.
 */
class PromptPiiMinimizer
{
    /**
     * Redact obvious PII from free text, preserving vehicle/service semantics.
     */
    public static function redact(string $text): string
    {
        if ($text === '') {
            return '';
        }

        // 1) Email addresses -> [email]
        $text = preg_replace(
            '/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/',
            '[email]',
            $text
        );

        // 2) Very long bare digit runs (>= 13 contiguous digits) -> [number].
        // These are card / account / long reference numbers, never service
        // data. Handled before the phone rule so they are labelled precisely.
        // A VIN's longest digit run is short (and it is alphanumeric), and
        // plate numbers are short, so neither is affected.
        $text = preg_replace('/\b\d{13,}\b/', '[number]', $text);

        // 3) Phone numbers -> [phone].
        // Optional leading + then a run of digits with common separators
        // (space, dash, dot, parentheses) totalling 7+ digits. The alphanumeric
        // boundary guards ensure we only match free-standing numbers, so a
        // digit run embedded inside an alphanumeric token (e.g. a VIN like
        // JT2BF22K1W0123456 or a plate token) is left intact. The 7-digit floor
        // keeps ordinary short numbers (years, prices, quantities, 2-5 digit
        // plate numbers) while catching UAE and international phone formats such
        // as +971 50 123 4567 or 0501234567.
        $text = preg_replace_callback(
            '/(?<![A-Za-z0-9])\+?\d[\d\s().\-]{5,}\d(?![A-Za-z0-9])/',
            static function (array $m): string {
                $digitCount = preg_match_all('/\d/', $m[0]);

                return $digitCount >= 7 ? '[phone]' : $m[0];
            },
            $text
        );

        return $text;
    }

    /**
     * Build a neutral lead-context descriptor for the prompt. Deliberately
     * drops name, phone and email; keeps only non-identifying signals that help
     * the model (whether a vehicle make/model is already known, conversation
     * state, and last intent).
     *
     * @param  array<string, mixed>  $lead
     * @return array<int, string>
     */
    public static function neutralLeadDescriptor(array $lead): array
    {
        $bits = [];

        $hasMake = ! empty($lead['vehicle_make_id']) || ! empty($lead['other_make']);
        $hasModel = ! empty($lead['vehicle_model_id']) || ! empty($lead['other_model']);

        if ($hasMake) {
            $bits[] = 'make=known';
        }
        if ($hasModel) {
            $bits[] = 'model=known';
        }
        if (! empty($lead['conversation_state'])) {
            $bits[] = 'state='.$lead['conversation_state'];
        }
        if (! empty($lead['last_intent'])) {
            $bits[] = 'last_intent='.$lead['last_intent'];
        }

        return $bits;
    }
}
