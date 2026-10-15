(() => {
  'use strict';

  const form = document.querySelector('[data-registration-form]');
  if (!form) return;
  const fields = [...form.querySelectorAll('input[required]')];
  const password = form.querySelector('[name="password"]');
  const confirmation = form.querySelector('[name="password_confirm"]');

  const validate = (field) => {
    if (field.type !== 'password' && field.type !== 'checkbox') field.value = field.value.trim();
    field.setCustomValidity('');
    let message = '';
    if (field === confirmation && password && field.value && field.value !== password.value) {
      message = 'The passwords must match.';
    } else if (field.validity.valueMissing || (field.type === 'password' && !field.value.trim())) {
      message = field.type === 'checkbox' ? 'Please agree to the Terms and Conditions.' : 'This field is required.';
    } else if (field.validity.typeMismatch) {
      message = 'Please enter a valid email address.';
    } else if (field.minLength > 0 && [...field.value].length < field.minLength) {
      message = `Please use at least ${field.minLength} characters.`;
    } else if (field.type === 'tel' && field.value && !/^[+0-9(). \-]{7,30}$/.test(field.value)) {
      message = 'Enter a phone number with 7 to 30 characters using digits, spaces, +, (, ), . or -.';
    }
    field.setCustomValidity(message);
    const valid = field.validity.valid;
    field.classList.toggle('is-invalid', !valid);
    field.setAttribute('aria-invalid', String(!valid));
    const feedback = document.getElementById(`${field.id}Error`);
    if (feedback) {
      feedback.textContent = message || field.validationMessage;
      const describedBy = new Set((field.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
      describedBy.add(feedback.id);
      field.setAttribute('aria-describedby', [...describedBy].join(' '));
    }
    return valid;
  };

  form.addEventListener('focusout', (event) => {
    if (fields.includes(event.target)) validate(event.target);
  });
  form.addEventListener('input', (event) => {
    if (fields.includes(event.target) && event.target.classList.contains('is-invalid')) validate(event.target);
    if (event.target === password && confirmation?.value) validate(confirmation);
  });
  // Run before the shared loading handler so invalid forms never enter a loading state.
  form.addEventListener('submit', (event) => {
    const valid = fields.map(validate).every(Boolean);
    if (!valid) {
      event.preventDefault();
      event.stopImmediatePropagation();
      fields.find((field) => !field.validity.valid)?.focus();
    }
  }, true);
})();
