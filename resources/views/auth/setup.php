<main class="grid min-h-screen place-items-center px-5 py-12"><section class="w-full max-w-lg rounded-3xl border border-slate-800 bg-slate-900/80 p-8 shadow-2xl sm:p-10">
  <img src="<?= \App\Core\Url::to('/favicon.png') ?>" alt="WSIT School logo" class="mb-7 h-12 w-12 rounded-2xl object-cover shadow-[0_0_16px_rgba(34,197,94,0.2)]"><p class="text-sm font-semibold text-green-300">First-time setup</p><h1 class="mt-2 text-3xl font-semibold">Create your administrator</h1><p class="mt-3 text-white">This form is available only until the first account is created.</p>
  <form method="post" action="<?= \App\Core\Url::to('/setup') ?>" class="mt-8 space-y-4"><?= \App\Core\Csrf::field() ?>
    <label class="block text-sm text-slate-300">Full name<input required name="name" autocomplete="name" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-green-400"></label>
    <label class="block text-sm text-slate-300">Email address<input required type="email" name="email" autocomplete="email" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-green-400"></label>
    <label class="block text-sm text-slate-300">Password<input required type="password" name="password" minlength="10" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-green-400"></label>
    <label class="block text-sm text-slate-300">Confirm password<input required type="password" name="password_confirmation" minlength="10" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 outline-none focus:border-green-400"></label>
    <button class="form-action-button w-full">Create administrator</button>
  </form>
</section></main>
