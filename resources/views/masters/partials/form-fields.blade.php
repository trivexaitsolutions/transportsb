<div class="grid grid-cols-1 gap-4 md:grid-cols-2" data-master-fields-grid>
    @foreach($config['fields'] as $field)
        @if($field['type'] === 'checkbox')
            <label data-field-wrap="{{ $field['name'] }}" data-create-hidden="{{ !empty($field['create_hidden']) ? '1' : '0' }}" class="{{ !empty($field['wide']) ? 'md:col-span-2' : '' }} flex items-center gap-3 border border-slate-200 bg-slate-50 p-3 font-bold text-slate-700">
                <input type="checkbox" name="{{ $field['name'] }}" value="1" data-master-field="{{ $field['name'] }}" @checked($field['default'] ?? ($field['name'] === 'is_active')) class="h-5 w-5">
                {{ $field['label'] }}
            </label>
        @else
            <label data-field-wrap="{{ $field['name'] }}" data-create-hidden="{{ !empty($field['create_hidden']) ? '1' : '0' }}" class="{{ !empty($field['wide']) ? 'md:col-span-2' : '' }}">
                <span class="mb-1 block text-xs font-black uppercase tracking-wide text-slate-600">{{ $field['label'] }} @if(!empty($field['required']))<span class="text-red-600">*</span>@endif</span>
                @if($field['type'] === 'textarea')
                    <textarea name="{{ $field['name'] }}" data-master-field="{{ $field['name'] }}" data-default-value="{{ $field['default'] ?? '' }}" class="master-textarea quick-master-textarea" {{ !empty($field['required']) ? 'required' : '' }}>{{ $field['default'] ?? '' }}</textarea>
                @elseif($field['type'] === 'select')
                    <select name="{{ $field['name'] }}" data-master-field="{{ $field['name'] }}" class="master-input quick-master-input" {{ !empty($field['required']) ? 'required' : '' }}>
                        <option value="">Select {{ $field['label'] }}</option>
                        @foreach(($field['options'] ?? []) as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" data-master-field="{{ $field['name'] }}" class="master-input quick-master-input" @if($field['type'] === 'number') step="0.01" @endif {{ !empty($field['required']) ? 'required' : '' }}>
                @endif
            </label>
        @endif
    @endforeach
</div>
