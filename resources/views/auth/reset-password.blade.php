@extends('auth.layout')

@section('title', __('app.auth.reset_title'))

@section('subtitle', __('app.auth.reset_subtitle'))

@section('form')
    <form method="POST" action="/reset-password" class="mt-8 flex flex-col gap-5">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <label class="flex flex-col gap-1.5 text-sm">
            <span class="font-medium text-slate-700">{{ __('app.auth.email') }}</span>

            <input
                name="email"
                type="email"
                value="{{ old('email', $email) }}"
                autocomplete="email"
                required
                readonly
                class="rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-600 outline-none"
            >

            @error('email')
                <span class="text-xs text-red-600">{{ $message }}</span>
            @enderror
        </label>

        @include('auth.field', [
            'label' => __('app.auth.new_password'),
            'name' => 'password',
            'type' => 'password',
            'autocomplete' => 'new-password',
            'autofocus' => true,
        ])

        @include('auth.field', [
            'label' => __('app.auth.password_confirm'),
            'name' => 'password_confirmation',
            'type' => 'password',
            'autocomplete' => 'new-password',
        ])

        <button
            type="submit"
            class="mt-1 rounded-xl bg-fuchsia-700 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-fuchsia-700/20 transition hover:bg-fuchsia-800 focus:ring-4 focus:ring-fuchsia-100 focus:outline-none"
        >
            {{ __('app.auth.reset_button') }}
        </button>
    </form>
@endsection

@section('footer')
    <a href="/login" class="font-semibold text-fuchsia-700 hover:text-fuchsia-800">{{ __('app.auth.back_to_login') }}</a>
@endsection
