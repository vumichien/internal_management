@extends('layouts.app')

@section('title', 'Server Error')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 text-center">
        <div>
            <h1 class="text-9xl font-bold text-red-300">500</h1>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Server Error
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Something went wrong on our end. We're working to fix it.
            </p>
        </div>
        
        <div class="mt-8 space-y-4">
            <a href="{{ route('dashboard') }}" 
               class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Go to Dashboard
            </a>
            
            <button onclick="window.location.reload()" 
                    class="group relative w-full flex justify-center py-2 px-4 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Try Again
            </button>
        </div>
        
        <div class="mt-6">
            <p class="text-xs text-gray-500">
                Error ID: {{ uniqid() }} - Please include this when contacting support.
            </p>
        </div>
    </div>
</div>
@endsection 