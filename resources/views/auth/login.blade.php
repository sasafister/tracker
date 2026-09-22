@extends('auth.layout')

@section('title', __('app.auth.login_title'))

@section('subtitle', __('app.auth.login_subtitle'))

@section('form')
    @include('auth.status')

    <form method="POST" action="/login" class="mt-8 flex flex-col gap-5">
        @csrf

        @include('auth.field', [
            'label' => __('app.auth.email'),
            'name' => 'email',
            'type' => 'email',
            'autocomplete' => 'email',
            'placeholder' => __('app.auth.email_placeholder'),
            'autofocus' => true,
        ])

        @include('auth.field', [
            'label' => __('app.auth.password'),
            'name' => 'password',
            'type' => 'password',
            'autocomplete' => 'current-password',
        ])

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input name="remember" type="checkbox" value="1" class="size-4 rounded border-slate-300 accent-fuchsia-700">
                {{ __('app.auth.remember') }}
            </label>

            <a href="/forgot-password" class="text-sm font-medium text-fuchsia-700 hover:text-fuchsia-800">
                {{ __('app.auth.forgot_link') }}
            </a>
        </div>

        <button
            type="submit"
            class="mt-1 rounded-xl bg-fuchsia-700 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-fuchsia-700/20 transition hover:bg-fuchsia-800 focus:ring-4 focus:ring-fuchsia-100 focus:outline-none"
        >
            {{ __('app.auth.login_button') }}
        </button>
    </form>
@endsection

@section('footer')
    {{ __('app.auth.no_account') }} <a href="/register" class="font-semibold text-fuchsia-700 hover:text-fuchsia-800">{{ __('app.auth.register_link') }}</a>
@endsection
