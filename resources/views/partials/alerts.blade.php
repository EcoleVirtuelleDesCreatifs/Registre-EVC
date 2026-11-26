@if (session('status'))<div class="alert ok">{{ session('status') }}</div>@endif
@error('workflow')<div class="alert err">{{ $message }}</div>@enderror
@error('backup')<div class="alert err">{{ $message }}</div>@enderror
@error('ids')<div class="alert err">{{ $message }}</div>@enderror
@error('holder')<div class="alert err">{{ $message }}</div>@enderror
@if ($errors->any() && ! $errors->has('workflow') && ! $errors->has('backup') && ! $errors->has('ids') && ! $errors->has('holder') && ! request()->routeIs('admin.login', 'admin.2fa.*'))
    <div class="alert err">Le formulaire contient des erreurs : vérifiez les champs signalés.</div>
@endif
