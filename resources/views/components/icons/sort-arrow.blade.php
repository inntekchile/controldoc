@props(['direction'])

@if ($direction === 'asc')
    <svg {{ $attributes->merge(['class' => 'inline w-4 h-4 ml-1 text-gray-500 dark:text-gray-400']) }} fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L10 12.586l3.293-3.293a1 1 0 011.414 0z" clip-rule="evenodd" />
    </svg>
@elseif ($direction === 'desc')
    <svg {{ $attributes->merge(['class' => 'inline w-4 h-4 ml-1 text-gray-500 dark:text-gray-400']) }} fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
    </svg>
@else
    {{-- Ícono neutral para indicar que la columna es ordenable pero no está activa --}}
    <svg {{ $attributes->merge(['class' => 'inline w-4 h-4 ml-1 text-gray-400 dark:text-gray-500 opacity-50']) }} fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
    </svg>
@endif