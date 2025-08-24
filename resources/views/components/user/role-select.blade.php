{{-- Reusable role select bound to \App\Models\User::ROLES --}}
@php($roles = \App\Models\User::ROLES)
@props([
  'name' => 'role',
  'value' => null,
  'required' => true,
  'label' => 'Role',
  'selectClass' => 'form-select w-full',
  'labelClass' => 'block font-medium text-sm text-gray-700',
])

<div>
  <label class="{{ $labelClass }}">{{ $label }}</label>
  <select name="{{ $name }}" class="{{ $selectClass }}" @if($required) required @endif>
    @foreach($roles as $key)
      <option value="{{ $key }}" @selected(old($name, $value) === $key)>{{ ucfirst($key) }}</option>
    @endforeach
  </select>
</div>
