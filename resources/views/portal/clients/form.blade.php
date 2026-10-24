@extends('portal.layout')
@section('title', $account ? 'Manage '.$account->name : 'Add client')
@section('content')
@php
  use App\Support\Ui;
  $assigned = collect(old('team', $account ? $account->team->pluck('id')->all() : []));
  $me = auth()->user();
  $canTeam = $me->role === 'owner' || $me->hasPerm('team.manage');
@endphp
<a class="back" href="/portal/clients">{!! \App\Support\Ui::icon('arrow') !!}All clients</a>
<div class="pagehead"><h1>{{ $account ? $account->name : 'Add client' }}</h1></div>

<div class="grid client-layout {{ $account ? 'g-main' : '' }}">
  <form class="pn client-form" method="post" enctype="multipart/form-data" action="{{ $account ? '/portal/clients/'.$account->id : '/portal/clients' }}">
    @csrf @if($account) @method('PUT') @endif
    <h3>Client details</h3>
    <div class="client-fields">
    <div class="client-top">
      <div class="fld"><label>Logo</label>
        <x-image-input name="logo" remove-name="remove_logo" :current="$account?->logoUrl()" label="Upload logo" hint="PNG, JPG or WEBP" />
      </div>
      <div class="stack" style="margin:0">
        <div class="fld" style="margin:0"><label for="name">Client / brand name</label><input id="name" name="name" value="{{ old('name', $account?->name) }}" required maxlength="80"></div>
        <div class="fld" style="margin:0"><label>Billing currency</label>
          @if($account && $account->invoices()->exists())
            <input value="{{ $account->currency }} · {{ $account->currencyName() }}" disabled><div class="hint">Locked because this client already has invoices.</div>
          @else
            <select name="currency" data-search="5" required>@foreach(config('currencies') as $code => $name)<option value="{{ $code }}" @selected(old('currency', $account?->currency ?? config('portal.currency')) === $code)>{{ $code }} — {{ $name }}</option>@endforeach</select>
          @endif
        </div>
      </div>
    </div>

    <div class="client-trio" style="margin-top:14px">
      <div class="fld"><label>Mobile number</label><input type="tel" name="phone" value="{{ old('phone', $account?->phone) }}" maxlength="30" placeholder="+971 50 123 4567"></div>
      <div class="fld"><label>Tax / VAT number</label><input name="tax_id" value="{{ old('tax_id', $account?->tax_id) }}" maxlength="60" placeholder="Optional"></div>
      <div class="fld"><label>Billing address</label><input name="billing_address" value="{{ old('billing_address', $account?->billing_address) }}" maxlength="500" placeholder="Printed under “Billed to” on invoices"></div>
    </div>

    @unless($account)
      <h3 style="margin-top:20px">First login</h3>
      <div class="client-trio"><div class="fld"><label>Contact name</label><input name="client_name" value="{{ old('client_name') }}" required maxlength="80"></div>
        <div class="fld"><label>Email</label><input type="email" name="client_email" value="{{ old('client_email') }}" required></div>
        <div class="fld"><label>Password</label><input name="client_password" minlength="8" maxlength="60" placeholder="Leave empty to generate one"></div></div>
    @endunless

    @if($canTeam || ! $account)
      <h3 style="margin-top:20px">Muannas team on this client</h3>
      <div class="team-chips">
        @foreach($staff as $s)
          <label class="chip"><input type="checkbox" name="team[]" value="{{ $s->id }}" @checked($assigned->contains($s->id))> {{ $s->name }} <small>{{ $s->roleLabel() }}</small></label>
        @endforeach
      </div>
      <p class="hint mute" style="margin-top:-6px">Team members only see the clients assigned to them (unless their role can access every client). Admins always see everyone and get the email alerts.</p>
    @endif
    <button class="btn primary" style="justify-self:start">{{ $account ? 'Save client' : 'Add client' }}</button>
    </div>
  </form>

  @if($account)
  <div class="stack">
    <div class="pn">
      <h3>Client logins</h3>
      @forelse($account->clientUsers as $cu)
        <div class="file"><div class="nm"><b>{{ $cu->name }}</b><small>{{ $cu->email }}</small></div>
          <form method="post" action="/portal/clients/{{ $account->id }}/users/{{ $cu->id }}/reset" data-confirm="Set a new password for {{ $cu->name }}?" style="margin:0">@csrf<button class="btn sm">New password</button></form>
          <form method="post" action="/portal/clients/{{ $account->id }}/users/{{ $cu->id }}" data-confirm="Remove {{ $cu->name }}'s login?" style="margin:0">@csrf @method('DELETE')<button class="ibtn" aria-label="Remove login">{!! Ui::icon('trash') !!}</button></form></div>
      @empty
        <p class="mute">No logins yet.</p>
      @endforelse
      <form method="post" action="/portal/clients/{{ $account->id }}/users" style="margin-top:14px">@csrf
        <div class="fld"><label>Name</label><input name="name" required maxlength="80"></div>
        <div class="fld"><label>Email</label><input type="email" name="email" required></div>
        <div class="fld"><label>Password</label><input name="password" minlength="8" maxlength="60" placeholder="Leave empty to generate one"></div>
        <button class="btn sm primary">Add login</button>
      </form>
    </div>
    @if($me->hasPerm('workspaces.delete'))
      <div class="pn">
        <h3>Remove client</h3><p class="mute">Deletes the client, every post, file, invoice and report, and the client's logins. This cannot be undone.</p>
        <form method="post" action="/portal/clients/{{ $account->id }}" data-confirm="Permanently delete {{ $account->name }} and everything in it?">@csrf @method('DELETE')<button class="btn danger sm">Delete client</button></form>
      </div>
    @endif
  </div>
  @endif
</div>
@endsection
