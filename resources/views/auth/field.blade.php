<label class="flex flex-col gap-1.5 text-sm">
    <span class="font-medium text-slate-700">{{ $label }}</span>

    <input
        name="{{ $name }}"
        type="{{ $type ?? 'text' }}"
        value="{{ ($type ?? 'text') === 'password' ? '' : old($name) }}"
        autocomplete="{{ $autocomplete ?? 'off' }}"
        placeholder="{{ $placeholder ?? '' }}"
        @if ($autofocus ?? false) autofocus @endif
        required
        @class([
            'rounded-xl border bg-white px-3.5 py-2.5 text-sm shadow-sm outline-none transition placeholder:text-slate-400 focus:ring-4',
            'border-red-300 focus:border-red-400 focus:ring-red-100' => $errors->has($name),
            'border-slate-200 focus:border-fuchsia-400 focus:ring-fuchsia-100' => ! $errors->has($name),
        ])
    >

    @error($name)
        <span class="text-xs text-red-600">{{ $message }}</span>
    @enderror
</label>
