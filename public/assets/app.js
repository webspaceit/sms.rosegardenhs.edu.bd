(() => {
  'use strict';

  let activeRequest = null;
  let requestNumber = 0;
  const confirmedForms = new WeakSet();

  const progressStyle = document.createElement('style');
  progressStyle.textContent = 'body[data-ajax-loading="true"]::before{content:"";position:fixed;z-index:9999;inset:0 100% auto 0;height:2px;background:#22c55e;animation:ajax-progress 1.1s ease-in-out infinite}@keyframes ajax-progress{0%{inset-inline-end:100%}55%{inset-inline-end:18%}100%{inset-inline-end:0}}';
  document.head.append(progressStyle);

  function showToast(message) {
    document.getElementById('app-toast')?.remove();
    const toast = document.createElement('div');
    toast.id = 'app-toast';
    toast.className = 'toast-enter fixed bottom-5 right-5 z-50 flex max-w-sm items-center gap-3 rounded-xl border border-red-400/30 bg-red-950 px-4 py-3 text-red-100 shadow-2xl';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    const icon = document.createElement('span');
    icon.className = 'grid h-7 w-7 shrink-0 place-items-center rounded-full bg-red-400/15 text-red-300';
    icon.textContent = '!';
    const text = document.createElement('span');
    text.className = 'text-sm';
    text.textContent = message;
    const dismiss = document.createElement('button');
    dismiss.type = 'button';
    dismiss.className = 'ml-2 text-white hover:text-white';
    dismiss.setAttribute('aria-label', 'Dismiss');
    dismiss.textContent = '×';
    dismiss.addEventListener('click', () => toast.remove());
    toast.append(icon, text, dismiss);
    document.body.append(toast);
    window.setTimeout(() => toast.remove(), 5000);
  }

  function confirmInToast(message, onConfirm) {
    document.getElementById('app-toast')?.remove();
    const toast = document.createElement('div');
    toast.id = 'app-toast';
    toast.className = 'toast-enter fixed bottom-5 right-5 z-50 flex max-w-[min(28rem,calc(100vw-2rem))] flex-wrap items-center gap-3 rounded-xl border border-green-300/25 bg-[#062b20] px-4 py-3 text-green-50 shadow-2xl';
    toast.setAttribute('role', 'alertdialog');
    toast.setAttribute('aria-live', 'assertive');
    const icon = document.createElement('span');
    icon.className = 'grid h-8 w-8 shrink-0 place-items-center rounded-full border border-green-200/20 bg-green-300/10 text-green-200';
    icon.textContent = '?';
    const text = document.createElement('span');
    text.className = 'min-w-0 flex-1 text-sm';
    text.textContent = message;
    const actions = document.createElement('span');
    actions.className = 'ml-auto flex shrink-0 items-center gap-2';
    const cancel = document.createElement('button');
    cancel.type = 'button';
    cancel.className = 'rounded-lg border border-green-100/20 px-3 py-2 text-xs font-medium text-green-100/80 transition hover:bg-white/5';
    cancel.textContent = 'Cancel';
    const confirm = document.createElement('button');
    confirm.type = 'button';
    confirm.className = 'rounded-lg border border-green-300/30 bg-green-400/15 px-3 py-2 text-xs font-semibold text-green-100 transition hover:bg-green-400/25';
    confirm.textContent = 'Confirm';
    const dismiss = () => toast.remove();
    cancel.addEventListener('click', dismiss);
    confirm.addEventListener('click', () => {
      dismiss();
      onConfirm();
    });
    toast.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') dismiss();
    });
    actions.append(cancel, confirm);
    toast.append(icon, text, actions);
    document.body.append(toast);
    confirm.focus();
    window.setTimeout(dismiss, 15000);
  }

  function filterMenu(searchInput) {
    const sidebar = searchInput.closest('#sidebar');
    if (!sidebar) return;
    const query = searchInput.value.trim().toLocaleLowerCase();
    const groups = [...sidebar.querySelectorAll('.nav-group')];
    const clearButton = sidebar.querySelector('#nav-search-clear');
    const emptyMessage = sidebar.querySelector('#nav-search-empty');
    let hasResults = false;

    function filterList(list, revealAll = false) {
      let listHasMatch = false;
      for (const item of list.children) {
        if (!(item instanceof HTMLLIElement)) continue;
        const label = [...item.children].find((child) => child.matches('a, [aria-disabled="true"]'));
        const labelMatches = !query || revealAll || (label?.textContent || '').toLocaleLowerCase().includes(query);
        const nestedList = [...item.children].find((child) => child instanceof HTMLUListElement);
        const childMatches = nestedList ? filterList(nestedList, labelMatches && Boolean(query)) : false;
        const visible = !query || labelMatches || childMatches;
        item.hidden = !visible;
        listHasMatch = listHasMatch || visible;
      }
      return listHasMatch;
    }

    for (const group of groups) {
      const groupLabel = group.querySelector(':scope > summary')?.textContent || '';
      const groupMatches = Boolean(query) && groupLabel.toLocaleLowerCase().includes(query);
      const list = [...group.children].find((child) => child instanceof HTMLUListElement);
      const itemMatches = list ? filterList(list, groupMatches) : false;
      const visible = !query || groupMatches || itemMatches;
      group.hidden = !visible;
      group.open = query ? visible : group.dataset.initialOpen === 'true';
      hasResults = hasResults || visible;
    }

    if (clearButton) clearButton.classList.toggle('hidden', query.length === 0);
    if (emptyMessage) emptyMessage.classList.toggle('hidden', !query || hasResults);
  }

  function revealStudentActions(menu, reveal) {
    const controls = menu.querySelector('.student-actions-controls');
    if (!controls) return;
    // A closed <details> hides its contents at the browser level, so changing
    // the controls' display alone cannot reveal the actions on hover.
    if (reveal) {
      menu.open = true;
      return;
    }

    if (!menu.contains(document.activeElement)) {
      menu.open = false;
    }
  }

  function runInsertedScripts(root) {
    root.querySelectorAll('script').forEach((oldScript) => {
      const script = document.createElement('script');
      for (const attribute of oldScript.attributes) script.setAttribute(attribute.name, attribute.value);
      script.textContent = oldScript.textContent;
      oldScript.replaceWith(script);
    });
  }

  function updateDocument(html) {
    const nextDocument = new DOMParser().parseFromString(html, 'text/html');
    if (!nextDocument.body || !nextDocument.title) throw new Error('The page response was incomplete.');

    document.title = nextDocument.title;
    document.documentElement.lang = nextDocument.documentElement.lang || 'en';
    for (const attribute of [...document.body.attributes]) {
      if (!nextDocument.body.hasAttribute(attribute.name)) document.body.removeAttribute(attribute.name);
    }
    for (const attribute of [...nextDocument.body.attributes]) {
      document.body.setAttribute(attribute.name, attribute.value);
    }
    document.body.replaceChildren(...[...nextDocument.body.childNodes].map((node) => document.importNode(node, true)));
    runInsertedScripts(document.body);
  }

  async function loadPage(url, { method = 'GET', body = undefined, push = true, scroll = true } = {}) {
    const target = new URL(url, window.location.href);
    if (target.origin !== window.location.origin) {
      window.location.assign(target.href);
      return;
    }

    activeRequest?.abort();
    activeRequest = new AbortController();
    const thisRequest = ++requestNumber;
    const sidebarScrollTop = document.querySelector('.sidebar-scroll')?.scrollTop ?? 0;
    document.body.dataset.ajaxLoading = 'true';

    try {
      const response = await fetch(target.href, {
        method,
        body,
        credentials: 'same-origin',
        redirect: 'follow',
        signal: activeRequest.signal,
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
      });
      if (thisRequest !== requestNumber) return;

      const contentType = response.headers.get('content-type') || '';
      if (!contentType.includes('text/html')) {
        window.location.assign(response.url || target.href);
        return;
      }

      const html = await response.text();
      if (thisRequest !== requestNumber) return;
      const finalUrl = new URL(response.url || target.href, window.location.href);
      updateDocument(html);
      requestAnimationFrame(() => {
        const sidebar = document.querySelector('.sidebar-scroll');
        if (sidebar) sidebar.scrollTop = sidebarScrollTop;
      });
      if (push) {
        window.history.replaceState({ ...(window.history.state || {}), scrollY: window.scrollY }, '', window.location.href);
        window.history.pushState({ scrollY: 0 }, '', finalUrl.href);
      }
      if (scroll) window.scrollTo(0, 0);
    } catch (error) {
      if (error.name !== 'AbortError') {
        showToast('Could not complete the request. Check your connection and try again.');
        document.dispatchEvent(new CustomEvent('app:ajax-error', { detail: { error, url: target.href, method } }));
      }
    } finally {
      if (thisRequest === requestNumber) {
        delete document.body.dataset.ajaxLoading;
        activeRequest = null;
      }
    }
  }

  document.addEventListener('click', (event) => {
    const clearSearch = event.target.closest('#nav-search-clear');
    if (clearSearch) {
      const searchInput = clearSearch.closest('#sidebar')?.querySelector('#nav-search');
      if (searchInput) {
        searchInput.value = '';
        filterMenu(searchInput);
        searchInput.focus();
      }
      return;
    }
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const anchor = event.target.closest('a[href]');
    if (!anchor || anchor.hasAttribute('download') || anchor.target && anchor.target !== '_self' || anchor.dataset.noAjax !== undefined || anchor.relList.contains('external')) return;
    const target = new URL(anchor.href, window.location.href);
    if (!['http:', 'https:'].includes(target.protocol) || target.origin !== window.location.origin) return;
    if (target.pathname === window.location.pathname && target.search === window.location.search && target.hash) return;
    event.preventDefault();
    loadPage(target.href);
  });

  document.addEventListener('input', (event) => {
    if (event.target instanceof HTMLInputElement && event.target.id === 'nav-search') {
      filterMenu(event.target);
    }
  });

  document.addEventListener('pointerover', (event) => {
    if (!(event.target instanceof Element)) return;
    const menu = event.target.closest('.student-actions');
    if (!menu || (event.relatedTarget instanceof Node && menu.contains(event.relatedTarget))) return;
    revealStudentActions(menu, true);
  });

  document.addEventListener('pointerout', (event) => {
    if (!(event.target instanceof Element)) return;
    const menu = event.target.closest('.student-actions');
    if (!menu || (event.relatedTarget instanceof Node && menu.contains(event.relatedTarget))) return;
    revealStudentActions(menu, false);
  });

  document.addEventListener('focusin', (event) => {
    if (event.target instanceof Element) {
      const menu = event.target.closest('.student-actions');
      if (menu) revealStudentActions(menu, true);
    }
  });

  document.addEventListener('focusout', (event) => {
    if (!(event.target instanceof Element)) return;
    const menu = event.target.closest('.student-actions');
    if (!menu || (event.relatedTarget instanceof Node && menu.contains(event.relatedTarget))) return;
    revealStudentActions(menu, false);
  });

  document.addEventListener('submit', (event) => {
    if (event.defaultPrevented) return;
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.dataset.noAjax !== undefined) return;
    const submitter = event.submitter;
    if (form.dataset.confirm && !confirmedForms.has(form)) {
      event.preventDefault();
      confirmInToast(form.dataset.confirm, () => {
        confirmedForms.add(form);
        if (submitter && submitter.isConnected) form.requestSubmit(submitter);
        else form.requestSubmit();
      });
      return;
    }
    confirmedForms.delete(form);
    const action = submitter?.formAction || form.action || window.location.href;
    const method = (submitter?.formMethod || form.method || 'GET').toUpperCase();
    const target = new URL(action, window.location.href);
    if (target.origin !== window.location.origin || !['GET', 'POST'].includes(method)) return;
    if ((submitter?.formTarget || form.target) && (submitter?.formTarget || form.target) !== '_self') return;

    event.preventDefault();
    const formData = submitter ? new FormData(form, submitter) : new FormData(form);
    if (method === 'GET') {
      target.search = '';
      for (const [key, value] of formData.entries()) {
        if (typeof value === 'string') target.searchParams.append(key, value);
      }
      loadPage(target.href);
      return;
    }
    loadPage(target.href, { method, body: formData, scroll: false });
  });

  window.addEventListener('popstate', (event) => {
    loadPage(window.location.href, { push: false, scroll: false }).then(() => {
      window.scrollTo(0, Number(event.state?.scrollY || 0));
    });
  });
})();
