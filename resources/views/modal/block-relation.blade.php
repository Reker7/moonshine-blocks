{{-- Block relation settings --}}
{{-- Rendered via FieldTypeHandler::modalView(), wrapped in x-show by the modal index --}}
<x-moonshine::form.wrapper :label="__('moonshine-blocks::ui.block_relation.title')">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Relation type --}}
        <div>
            <x-moonshine::form.label>
                {{ __('moonshine-blocks::ui.block_relation.relation_type') }}
            </x-moonshine::form.label>
            <select
                class="form-select"
                :value="getCurrentField()?.options?.relation_type || 'block'"
                @change="getCurrentField().options.relation_type = $event.target.value; getCurrentField().options.relation_target = ''"
            >
                <option value="block">{{ __('moonshine-blocks::ui.block_relation.type_block') }}</option>
                <option value="group">{{ __('moonshine-blocks::ui.block_relation.type_group') }}</option>
            </select>
            <p class="form-hint text-xs text-slate-400 mt-1">
                {{ __('moonshine-blocks::ui.block_relation.relation_type_hint') }}
            </p>
        </div>

        {{-- Relation target - Block (when relation_type = 'block') --}}
        <div x-show="(getCurrentField()?.options?.relation_type || 'block') === 'block'">
            <x-moonshine::form.label>
                {{ __('moonshine-blocks::ui.block_relation.select_block') }}
            </x-moonshine::form.label>
            <select
                class="form-select"
                x-model="getCurrentField().options.relation_target"
            >
                <option value="">{{ __('moonshine-blocks::ui.block_relation.select_placeholder') }}</option>
                <template x-for="b in (typesMeta[getCurrentField()?.type]?.blocks || [])" :key="b.value">
                    <option :value="b.value" x-text="b.label"></option>
                </template>
            </select>
            <p x-show="(typesMeta[getCurrentField()?.type]?.blocks || []).length === 0" class="form-hint text-xs text-amber-500 mt-1">
                {{ __('moonshine-blocks::ui.block_relation.no_blocks') }}
            </p>
            <p x-show="(typesMeta[getCurrentField()?.type]?.blocks || []).length > 0" class="form-hint text-xs text-slate-400 mt-1">
                {{ __('moonshine-blocks::ui.block_relation.select_block_hint') }}
            </p>
        </div>

        {{-- Relation target - Group (when relation_type = 'group') --}}
        <div x-show="getCurrentField()?.options?.relation_type === 'group'">
            <x-moonshine::form.label>
                {{ __('moonshine-blocks::ui.block_relation.select_group') }}
            </x-moonshine::form.label>
            <select
                class="form-select"
                x-model="getCurrentField().options.relation_target"
            >
                <option value="">{{ __('moonshine-blocks::ui.block_relation.select_placeholder') }}</option>
                <template x-for="g in (typesMeta[getCurrentField()?.type]?.groups || [])" :key="g.value">
                    <option :value="g.value" x-text="g.label"></option>
                </template>
            </select>
            <p x-show="(typesMeta[getCurrentField()?.type]?.groups || []).length === 0" class="form-hint text-xs text-amber-500 mt-1">
                {{ __('moonshine-blocks::ui.block_relation.no_groups') }}
            </p>
            <p x-show="(typesMeta[getCurrentField()?.type]?.groups || []).length > 0" class="form-hint text-xs text-slate-400 mt-1">
                {{ __('moonshine-blocks::ui.block_relation.select_group_hint') }}
            </p>
        </div>

        {{-- Multiple selection --}}
        <div class="md:col-span-2">
            <label class="flex items-center gap-2 cursor-pointer">
                <input
                    type="checkbox"
                    class="form-checkbox"
                    :checked="getCurrentField()?.options?.multiple"
                    @change="getCurrentField().options.multiple = $event.target.checked"
                >
                <span>{{ __('moonshine-blocks::ui.block_relation.multiple') }}</span>
            </label>
            <p class="form-hint text-xs text-slate-400 mt-1">
                {{ __('moonshine-blocks::ui.block_relation.multiple_hint') }}
            </p>
        </div>
    </div>
</x-moonshine::form.wrapper>
