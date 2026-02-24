{{-- Fieldset field type settings --}}
{{-- Rendered via FieldsetFieldType::modalView(), wrapped in x-show by the modal index --}}
<x-moonshine::form.wrapper :label="__('moonshine-blocks::ui.fieldset.select_label')">
    <div class="grid grid-cols-1 gap-4">

        {{-- Fieldset selector (replaces the key text input) --}}
        <div>
            <select
                class="form-select"
                :value="getCurrentField()?.key || ''"
                @change="getCurrentField().key = $event.target.value; resetFieldsetDefaults()"
            >
                <option value="">{{ __('moonshine-blocks::ui.fieldset.select_placeholder') }}</option>
                <template x-for="key in (typesMeta['fieldset']?.availableFieldsets || [])" :key="key">
                    <option :value="key" :selected="getCurrentField()?.key === key" x-text="key"></option>
                </template>
            </select>
            <p
                x-show="(typesMeta['fieldset']?.availableFieldsets || []).length === 0"
                class="form-hint text-xs text-amber-500 mt-1"
            >
                {{ __('moonshine-blocks::ui.fieldset.no_fieldsets') }}
            </p>
        </div>

        {{-- Default values for sub-fields --}}
        <div x-show="getCurrentField()?.key">
            <p class="text-sm font-medium text-slate-600 dark:text-slate-400 mb-2">
                {{ __('moonshine-blocks::ui.fieldset.defaults_label') }}
            </p>

            <template x-for="field in getFieldsetFields(getCurrentField()?.key)" :key="field.key">
                <div class="flex items-center gap-3 mb-2">
                    <label
                        class="w-1/3 text-sm text-slate-600 dark:text-slate-300 truncate"
                        x-text="field.name"
                    ></label>

                    {{-- Switcher → checkbox --}}
                    <template x-if="field.type === 'switcher'">
                        <input
                            type="checkbox"
                            class="form-checkbox"
                            :checked="getFieldsetDefault(field.key) === '1'"
                            @change="setFieldsetDefault(field.key, $event.target.checked ? '1' : '0')"
                        >
                    </template>

                    {{-- Everything else → text input --}}
                    <template x-if="field.type !== 'switcher'">
                        <input
                            type="text"
                            class="form-input w-2/3"
                            :value="getFieldsetDefault(field.key)"
                            @input="setFieldsetDefault(field.key, $event.target.value)"
                        >
                    </template>
                </div>
            </template>

            <p
                x-show="getFieldsetFields(getCurrentField()?.key).length === 0"
                class="form-hint text-xs text-slate-400"
            >
                {{ __('moonshine-blocks::ui.fieldset.no_defaults_available') }}
            </p>
        </div>

    </div>
</x-moonshine::form.wrapper>
