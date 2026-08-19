/**
 * Lightweight Jalali date/time picker for admin (no external deps).
 * Attach: <div class="jdp-field" data-jdp> + input.jdp-input + button[data-jdp-toggle]
 */
(function () {
  'use strict';

  var MONTHS = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
  var WEEK = ['ش','ی','د','س','چ','پ','ج'];

  function toLatin(s) {
    return String(s || '').replace(/[۰-۹]/g, function (d) {
      return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d);
    }).replace(/[٠-٩]/g, function (d) {
      return '٠١٢٣٤٥٦٧٨٩'.indexOf(d);
    });
  }

  function pad(n) { return (n < 10 ? '0' : '') + n; }

  function toJalali(gy, gm, gd) {
    var g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    var gy2 = (gm > 2) ? (gy + 1) : gy;
    var days = 355666 + (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100)
      + Math.floor((gy2 + 399) / 400) + gd + g_d_m[gm - 1];
    var jy = -1595 + (33 * Math.floor(days / 12053));
    days %= 12053;
    jy += 4 * Math.floor(days / 1461);
    days %= 1461;
    if (days > 365) {
      jy += Math.floor((days - 1) / 365);
      days = (days - 1) % 365;
    }
    var jm, jd;
    if (days < 186) {
      jm = 1 + Math.floor(days / 31);
      jd = 1 + (days % 31);
    } else {
      jm = 7 + Math.floor((days - 186) / 30);
      jd = 1 + ((days - 186) % 30);
    }
    return [jy, jm, jd];
  }

  function isJalaliLeap(jy) {
    // Approximate leap detection via gregorian round-trip of Esfand 30
    var g = toGregorian(jy, 12, 30);
    var back = toJalali(g[0], g[1], g[2]);
    return back[0] === jy && back[1] === 12 && back[2] === 30;
  }

  function monthLength(jy, jm) {
    if (jm <= 6) return 31;
    if (jm <= 11) return 30;
    return isJalaliLeap(jy) ? 30 : 29;
  }

  function toGregorian(jy, jm, jd) {
    jy += 1595;
    var days = -355668 + (365 * jy) + (Math.floor(jy / 33) * 8) + Math.floor(((jy % 33) + 3) / 4)
      + jd + ((jm < 7) ? ((jm - 1) * 31) : (((jm - 7) * 30) + 186));
    var gy = 400 * Math.floor(days / 146097);
    days %= 146097;
    if (days > 36524) {
      gy += 100 * Math.floor(--days / 36524);
      days %= 36524;
      if (days >= 365) days++;
    }
    gy += 4 * Math.floor(days / 1461);
    days %= 1461;
    if (days > 365) {
      gy += Math.floor((days - 1) / 365);
      days = (days - 1) % 365;
    }
    var gd = days + 1;
    var sal_a = [0, 31, ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    var gm = 1;
    while (gm <= 12 && gd > sal_a[gm]) {
      gd -= sal_a[gm];
      gm++;
    }
    return [gy, gm, gd];
  }

  function parseInput(str) {
    str = toLatin(str).trim();
    if (!str) return null;
    var m = str.match(/^(\d{3,4})[\/\-](\d{1,2})[\/\-](\d{1,2})(?:[T\s]+(\d{1,2}):(\d{2}))?/);
    if (!m) return null;
    return {
      jy: +m[1], jm: +m[2], jd: +m[3],
      H: m[4] !== undefined ? +m[4] : 0,
      i: m[5] !== undefined ? +m[5] : 0
    };
  }

  function formatParts(p) {
    return p.jy + '/' + pad(p.jm) + '/' + pad(p.jd) + ' ' + pad(p.H) + ':' + pad(p.i);
  }

  function todayParts() {
    var n = new Date();
    var j = toJalali(n.getFullYear(), n.getMonth() + 1, n.getDate());
    return { jy: j[0], jm: j[1], jd: j[2], H: n.getHours(), i: n.getMinutes() };
  }

  // day of week for Jalali date: 0=Saturday ... 6=Friday
  function jalaliWeekday(jy, jm, jd) {
    var g = toGregorian(jy, jm, jd);
    var d = new Date(g[0], g[1] - 1, g[2]);
    // JS: 0=Sun ... 6=Sat → convert to Sat=0
    return (d.getDay() + 1) % 7;
  }

  function buildPicker(field, input) {
    var pop = document.createElement('div');
    pop.className = 'jdp-pop';
    pop.hidden = true;
    field.appendChild(pop);

    var view = parseInput(input.value) || todayParts();
    var vy = view.jy, vm = view.jm;

    function setValue(jd) {
      var H = parseInt(pop.querySelector('[data-h]').value, 10) || 0;
      var i = parseInt(pop.querySelector('[data-i]').value, 10) || 0;
      input.value = formatParts({ jy: vy, jm: vm, jd: jd, H: H, i: i });
      input.dispatchEvent(new Event('change', { bubbles: true }));
      pop.hidden = true;
    }

    function render() {
      var selected = parseInput(input.value);
      var len = monthLength(vy, vm);
      var start = jalaliWeekday(vy, vm, 1);
      var html = '';
      html += '<div class="jdp-head">';
      html += '<button type="button" class="jdp-nav" data-nav="-1" aria-label="ماه قبل">›</button>';
      html += '<div class="jdp-title">' + MONTHS[vm - 1] + ' ' + vy + '</div>';
      html += '<button type="button" class="jdp-nav" data-nav="1" aria-label="ماه بعد">‹</button>';
      html += '</div>';
      html += '<div class="jdp-week">';
      for (var w = 0; w < 7; w++) html += '<span>' + WEEK[w] + '</span>';
      html += '</div><div class="jdp-days">';
      for (var b = 0; b < start; b++) html += '<span class="jdp-empty"></span>';
      for (var d = 1; d <= len; d++) {
        var isSel = selected && selected.jy === vy && selected.jm === vm && selected.jd === d;
        var isToday = (function () {
          var t = todayParts();
          return t.jy === vy && t.jm === vm && t.jd === d;
        })();
        html += '<button type="button" class="jdp-day' + (isSel ? ' is-selected' : '') + (isToday ? ' is-today' : '') + '" data-day="' + d + '">' + d + '</button>';
      }
      html += '</div>';
      html += '<div class="jdp-time">';
      html += '<label>ساعت <input data-h type="number" min="0" max="23" value="' + pad((selected && selected.H) || view.H || 0) + '"></label>';
      html += '<label>دقیقه <input data-i type="number" min="0" max="59" value="' + pad((selected && selected.i) || view.i || 0) + '"></label>';
      html += '<button type="button" class="jdp-today" data-today>امروز</button>';
      html += '<button type="button" class="jdp-clear" data-clear>پاک</button>';
      html += '</div>';
      pop.innerHTML = html;

      pop.querySelectorAll('[data-nav]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var dir = parseInt(btn.getAttribute('data-nav'), 10);
          vm += dir;
          if (vm < 1) { vm = 12; vy--; }
          if (vm > 12) { vm = 1; vy++; }
          render();
        });
      });
      pop.querySelectorAll('[data-day]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          setValue(parseInt(btn.getAttribute('data-day'), 10));
        });
      });
      pop.querySelector('[data-today]').addEventListener('click', function () {
        var t = todayParts();
        vy = t.jy; vm = t.jm;
        pop.querySelector('[data-h]').value = pad(t.H);
        pop.querySelector('[data-i]').value = pad(t.i);
        setValue(t.jd);
      });
      pop.querySelector('[data-clear]').addEventListener('click', function () {
        input.value = '';
        input.dispatchEvent(new Event('change', { bubbles: true }));
        pop.hidden = true;
      });
    }

    function open() {
      var p = parseInput(input.value);
      if (p) { vy = p.jy; vm = p.jm; view = p; }
      else { var t = todayParts(); vy = t.jy; vm = t.jm; view = t; }
      render();
      pop.hidden = false;
    }

    function close() { pop.hidden = true; }

    return { open: open, close: close, el: pop };
  }

  function initField(field) {
    var input = field.querySelector('.jdp-input, input[name="published_at"]');
    var toggle = field.querySelector('[data-jdp-toggle]');
    if (!input) return;
    var picker = buildPicker(field, input);
    if (toggle) {
      toggle.addEventListener('click', function (e) {
        e.preventDefault();
        if (picker.el.hidden) picker.open(); else picker.close();
      });
    }
    input.addEventListener('focus', function () {
      // optional: don't auto-open to avoid noise
    });
    document.addEventListener('click', function (e) {
      if (!field.contains(e.target)) picker.close();
    });
  }

  function bootJalali(root) {
    (root || document).querySelectorAll('[data-jdp]').forEach(function (field) {
      if (field.dataset.jdpReady === '1') return;
      field.dataset.jdpReady = '1';
      initField(field);
    });
  }
  document.addEventListener('DOMContentLoaded', function () { bootJalali(document); });
  document.addEventListener('admin:navigated', function () { bootJalali(document); });
  window.AdminJalali = { boot: bootJalali };
})();
