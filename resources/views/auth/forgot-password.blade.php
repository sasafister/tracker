@extends('auth.layout')

@section('title', __('app.auth.forgot_title'))

@section('subtitle', __('app.auth.forgot_subtitle'))

@section('form')
    @include('auth.status')

    <form method="POST" action="/forgot-password" class="mt-8 flex flex-col gap-5">
        @csrf

        @include('auth.field', [
            'label' => __('app.auth.email'),
            'name' => 'email',
            'type' => 'email',
            'autocomplete' => 'email',
            'placeholder' => __('app.auth.email_placeholder'),
            'autofocus' => true,
        ])

        <button
            type="submit"
            class="mt-1 rounded-xl bg-fuchsia-700 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-fuchsia-700/20 transition hover:bg-fuchsia-800 focus:ring-4 focus:ring-fuchsia-100 focus:outline-none"
        >
            {{ __('app.auth.forgot_button') }}
        </button>
    </form>
@endsection

@section('footer')
    {{ __('app.auth.remembered') }} <a href="/login" class="font-semibold text-fuchsia-700 hover:text-fuchsia-800">{{ __('app.auth.back_to_login') }}</a>
@endsection
