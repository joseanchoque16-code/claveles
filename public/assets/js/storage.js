// LocalStorage safe helpers (no rompe si el navegador bloquea storage)
window.getLocalStorageItem = function (key, fallback = null) {
  try {
    const v = window.localStorage.getItem(key);
    return (v === null || v === undefined || v === "") ? fallback : v;
  } catch (e) {
    return fallback;
  }
};

window.setLocalStorageItem = function (key, value) {
  try {
    window.localStorage.setItem(key, value);
    return true;
  } catch (e) {
    return false;
  }
};
