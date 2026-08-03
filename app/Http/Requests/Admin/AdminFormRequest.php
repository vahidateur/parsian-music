<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Support\PersianTextNormalizer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared boundary for every admin Record_Form validation contract.
 *
 * Authorization is NOT decided here. Each admin controller action resolves its
 * named Policy ability through the Authorization_Layer, so this request never
 * duplicates or shadows a policy decision.
 *
 * Failed validation keeps Laravel's default redirect-back behavior, which is
 * what preserves the submitted old input and the localized per-field message.
 *
 * Text fields declared in `normalizedFields()` are put into canonical form
 * before the rules run, and the admin Actions normalize the same fields with the
 * same contract before persistence, so a validated value and a persisted value
 * can never diverge.
 *
 * Requirements: 6.5, 6.6, 6.7
 */
abstract class AdminFormRequest extends FormRequest
{
    /** Upper bound of a `decimal(12, 2)` money column. */
    protected const MONEY_MAX = 9999999999.99;

    /** Upper bound of an `unsignedSmallInteger` column. */
    protected const SMALL_INTEGER_MAX = 65535;

    /** Upper bound of a signed `integer` column. */
    protected const INTEGER_MAX = 2147483647;

    /** Number of `unsignedTinyInteger` sort-order slots available to a collection. */
    protected const TINY_INTEGER_SLOTS = 256;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Field name => normalization mode of PersianTextNormalizer.
     *
     * @return array<string, string>
     */
    public function normalizedFields(): array
    {
        return [];
    }

    protected function prepareForValidation(): void
    {
        $fields = $this->normalizedFields();

        if ($fields === []) {
            return;
        }

        $this->merge(PersianTextNormalizer::fields(
            array_intersect_key($this->all(), $fields),
            $fields,
        ));
    }
}
