<main class="grid min-h-screen lg:grid-cols-[1.1fr_.9fr]">
  <section class="relative hidden overflow-hidden bg-gradient-to-br from-green-500/20 via-slate-950 to-green-950/50 p-12 lg:flex lg:flex-col lg:justify-between">
    <div class="absolute -left-28 top-24 h-80 w-80 rounded-full bg-green-400/10 blur-3xl"></div>
    <a href="<?= \App\Core\Url::to('/') ?>" class="relative flex items-center gap-3"><img src="<?= \App\Core\Url::to('/favicon.png') ?>" alt="WSIT School logo" class="h-11 w-11 rounded-xl object-cover shadow-[0_0_18px_rgba(34,197,94,0.25)]"><span class="font-semibold tracking-wide">WSIT <span class="text-white">SCHOOL</span></span></a>
    <div class="relative max-w-xl"><p class="mb-4 text-sm font-semibold uppercase tracking-[.25em] text-green-300">School management</p><h1 class="text-5xl font-semibold leading-tight">A clearer view of your school, every day.</h1><p class="mt-6 max-w-md text-lg leading-8 text-white">Bring student records, academics, attendance, and school operations together in one place.</p></div>
    <p class="relative text-sm text-slate-500">Made for schools in Bangladesh · বাংলা ও English</p>
  </section>
  <section class="flex items-center justify-center px-6 py-12">
    <div class="w-full max-w-md">
      <a href="<?= \App\Core\Url::to('/') ?>" class="mb-12 flex items-center gap-3 lg:hidden"><img src="<?= \App\Core\Url::to('/favicon.png') ?>" alt="WSIT School logo" class="h-10 w-10 rounded-xl object-cover"><span class="font-semibold">WSIT School</span></a>
      <p class="text-sm font-medium text-green-300">Welcome back</p><h2 class="mt-2 text-3xl font-semibold">Sign in to your account</h2><p class="mt-2 text-white">Enter your school account details to continue.</p>
      <form method="post" action="<?= \App\Core\Url::to('/login') ?>" class="mt-8 space-y-5">
        <?= \App\Core\Csrf::field() ?>
        <label class="block"><span class="mb-2 block text-sm font-medium text-slate-300">Email address</span><input required type="email" name="email" autocomplete="username" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 outline-none transition placeholder:text-white focus:border-green-400 focus:ring-2 focus:ring-green-400/15" placeholder="you@school.edu.bd"></label>
        <label class="block"><span class="mb-2 block text-sm font-medium text-slate-300">Password</span><input required type="password" name="password" autocomplete="current-password" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 outline-none transition placeholder:text-white focus:border-green-400 focus:ring-2 focus:ring-green-400/15" placeholder="Enter your password"></label>
        <button class="form-action-button w-full">Sign in</button>
      </form>
      <p class="mt-8 text-center text-xs leading-5 text-slate-500">Need initial access? Visit <a class="text-green-300 hover:text-green-200" href="<?= \App\Core\Url::to('/setup') ?>">first-time setup</a> after configuring the database.</p>
    </div>
  </section>
</main>
