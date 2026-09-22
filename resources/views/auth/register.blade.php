@extends('auth.layout')

@section('title', __('app.auth.register_title'))

@section('subtitle', __('app.auth.register_subtitle'))

@section('form')
    <form method="POST" action="/register" class="mt-8 flex flex-col gap-5">
        @csrf

        @include('auth.field', [
            'label' => __('app.auth.name'),
            'name' => 'name',
            'autocomplete' => 'name',
            'autofocus' => true,
        ])

        @include('auth.field', [
            'label' => __('app.auth.email'),
            'name' => 'email',
            'type' => 'email',
            'autocomplete' => 'email',
            'placeholder' => __('app.auth.email_placeholder'),
        ])

        @include('auth.field', [
            'label' => __('app.auth.password'),
            'name' => 'password',
            'type' => 'password',
            'autocomplete' => 'new-password',
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
            {{ __('app.auth.register_button') }}
        </button>
    </form>
@endsection

@section('footer')
    {{ __('app.auth.have_account') }} <a href="/login" class="font-semibold text-fuchsia-700 hover:text-fuchsia-800">{{ __('app.auth.login_link') }}</a>
@endsection
