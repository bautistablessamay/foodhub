// Auto-refresh pages with data-refresh (orders / chat), unless the user is typing
var sec = parseInt(document.body.dataset.refresh || '0', 10);
if (sec > 0) {
  setInterval(function () {
    var a = document.activeElement;
    if (a && /INPUT|TEXTAREA|SELECT/.test(a.tagName)) return;
    if ([].some.call(document.querySelectorAll('input[name=msg]'), function (i) { return i.value; })) return;
    location.reload();
  }, sec * 1000);
}
