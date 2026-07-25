@if (session('status'))
    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100">
        {{ session('status') }}
    </div>
@endif

@if (session('error'))
    <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
        Revisa los campos marcados antes de continuar.
    </div>
@endif
