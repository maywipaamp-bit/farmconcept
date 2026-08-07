/* TheFarmConcept — generic multi-step wizard controller.
   No wizard JS existed anywhere in the project before this (the `.stepper` component in
   components.css was visual-only). Built generic on purpose so it can be reused by any future
   step-wizard screen (e.g. Activity Management), not just the Form & Survey Builder.

   Expected markup:
     <div class="stepper">
       <div class="step-item" data-step="1"> ... <span class="step-circle">1</span> ... </div>
       <div class="step-line"></div>
       <div class="step-item" data-step="2"> ... </div>
       ...
     </div>
     <div id="wizard-panels">
       <section data-step-panel="1"> ... </section>
       <section data-step-panel="2"> ... </section>
       ...
     </div>

   Steps that don't apply to the current context (e.g. the scoring step for non-health-tracking
   forms) are handled by the page itself: hide the matching `.step-item`/`.step-line`/panel with
   a class or `hidden`, then call `wizard.setSteps([...])` with the remaining step numbers so
   next/prev and the done/active state skip over it correctly. */
(function () {
  window.TFC = window.TFC || {};

  window.TFC.createWizard = function (opts) {
    var steps = opts.steps.slice();
    var current = steps[0];

    function stepItem(n) { return opts.stepper.querySelector('.step-item[data-step="' + n + '"]'); }
    function stepPanel(n) { return opts.panelContainer.querySelector('[data-step-panel="' + n + '"]'); }

    function render() {
      var currentIdx = steps.indexOf(current);
      steps.forEach(function (n, idx) {
        var item = stepItem(n);
        var panel = stepPanel(n);
        if (item) {
          item.classList.toggle('is-active', n === current);
          item.classList.toggle('is-done', idx < currentIdx);
          var circle = item.querySelector('.step-circle');
          if (circle) circle.textContent = idx < currentIdx ? '✓' : String(idx + 1);
        }
        if (panel) panel.classList.toggle('is-active', n === current);
      });
    }

    function goTo(n) {
      if (steps.indexOf(n) === -1) return;
      current = n;
      render();
      if (opts.onChange) opts.onChange(current);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function next() {
      if (opts.validate && opts.validate(current) === false) return;
      var idx = steps.indexOf(current);
      if (idx < steps.length - 1) goTo(steps[idx + 1]);
    }

    function prev() {
      var idx = steps.indexOf(current);
      if (idx > 0) goTo(steps[idx - 1]);
    }

    function setSteps(newSteps) {
      steps = newSteps.slice();
      if (steps.indexOf(current) === -1) current = steps[0];
      render();
    }

    render();

    return {
      next: next,
      prev: prev,
      goTo: goTo,
      setSteps: setSteps,
      getCurrent: function () { return current; },
      getSteps: function () { return steps.slice(); }
    };
  };
})();
