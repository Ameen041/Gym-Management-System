/* Weight chart: one line over time, drawn to the width of its box.
   Time runs in the reading direction (right to left in Arabic).
   Pointer, touch or arrow keys show each point's weight and date. */
(function () {
  "use strict";
  var NS = "http://www.w3.org/2000/svg";

  function el(name, attrs, parent) {
    var n = document.createElementNS(NS, name);
    for (var k in attrs) n.setAttribute(k, attrs[k]);
    if (parent) parent.appendChild(n);
    return n;
  }
  function kg(v) { return String(Math.round(v * 10) / 10); }

  // clean ticks: 3-5 steps of 0.5 / 1 / 2 / 5 / 10 kg
  function scale(min, max) {
    var steps = [0.5, 1, 2, 5, 10], step = 10, i;
    for (i = 0; i < steps.length; i++) { if ((max - min) / steps[i] <= 4) { step = steps[i]; break; } }
    var lo = Math.floor(min / step) * step, hi = Math.ceil(max / step) * step;
    if (hi - lo < step * 2) { lo -= step; hi += step; }
    var list = [];
    for (var t = lo; t <= hi + 1e-9; t += step) list.push(Math.round(t * 10) / 10);
    return { lo: lo, hi: hi, list: list };
  }

  function draw(fig) {
    var pts = JSON.parse(fig.getAttribute("data-chart"));
    var W = fig.clientWidth;
    if (!W || pts.length < 2) return;
    var rtl = document.documentElement.getAttribute("dir") === "rtl";
    var H = W < 480 ? 190 : 230;
    var padTick = 40, padEnd = 46, padTop = 14, padBottom = 26;
    var plotL = rtl ? padEnd : padTick, plotR = W - (rtl ? padTick : padEnd);
    var ws = pts.map(function (p) { return p.w; });
    var s = scale(Math.min.apply(null, ws), Math.max.apply(null, ws));
    var t0 = pts[0].t, span = Math.max(pts[pts.length - 1].t - t0, 1);
    function x(t) { var f = (t - t0) / span; return rtl ? plotR - f * (plotR - plotL) : plotL + f * (plotR - plotL); }
    function y(w) { return padTop + (s.hi - w) / (s.hi - s.lo) * (H - padTop - padBottom); }

    var old = fig.querySelector("svg");
    if (old) old.remove();
    var svg = el("svg", { width: W, height: H, viewBox: "0 0 " + W + " " + H, "aria-hidden": "true" });
    fig.insertBefore(svg, fig.firstChild);

    var grid = el("g", { "class": "wchart-grid" }, svg);
    s.list.forEach(function (t) {
      el("line", { x1: plotL, x2: plotR, y1: y(t), y2: y(t) }, grid);
      var label = el("text", { x: rtl ? W - padTick + 8 : padTick - 8, y: y(t), "text-anchor": rtl ? "start" : "end", "dominant-baseline": "middle" }, grid);
      label.textContent = kg(t);
    });

    [0, pts.length - 1].forEach(function (i) {
      var d = el("text", { "class": "wchart-date", x: x(pts[i].t), y: H - 7, "text-anchor": i === 0 ? (rtl ? "end" : "start") : (rtl ? "start" : "end") }, svg);
      d.textContent = pts[i].a;
    });

    var xy = pts.map(function (p) { return [x(p.t), y(p.w)]; });
    el("path", { "class": "wchart-line", d: "M" + xy.map(function (p) { return p[0].toFixed(1) + "," + p[1].toFixed(1); }).join(" L") }, svg);
    xy.forEach(function (p) { el("circle", { "class": "wchart-dot", cx: p[0], cy: p[1], r: 4 }, svg); });

    var end = xy[xy.length - 1];
    var value = el("text", { "class": "wchart-value", x: rtl ? end[0] - 10 : end[0] + 10, y: end[1], "text-anchor": rtl ? "end" : "start", "dominant-baseline": "middle" }, svg);
    value.textContent = kg(pts[pts.length - 1].w);

    var cross = el("line", { "class": "wchart-cross", x1: 0, x2: 0, y1: padTop, y2: H - padBottom }, svg);
    var focus = el("circle", { "class": "wchart-focus", r: 6, cx: 0, cy: 0 }, svg);
    var tip = fig.querySelector(".wchart-tip");
    var unit = fig.getAttribute("data-unit") || "kg";

    fig._show = function (i) {
      var p = xy[i];
      fig._index = i;
      cross.setAttribute("x1", p[0]); cross.setAttribute("x2", p[0]);
      focus.setAttribute("cx", p[0]); focus.setAttribute("cy", p[1]);
      fig.classList.add("is-active");
      tip.querySelector("b").textContent = kg(pts[i].w) + " " + unit;
      tip.querySelector("span").textContent = pts[i].l + " · " + pts[i].d;
      tip.hidden = false;
      var half = tip.offsetWidth / 2;
      tip.style.left = Math.min(Math.max(p[0], half), W - half) + "px";
      tip.style.top = Math.max(p[1] - 14, 0) + "px";
    };
    fig._nearest = function (clientX) {
      var px = clientX - svg.getBoundingClientRect().left, best = 0;
      xy.forEach(function (p, i) { if (Math.abs(p[0] - px) < Math.abs(xy[best][0] - px)) best = i; });
      return best;
    };
    fig._count = xy.length;
    fig._rtl = rtl;
  }

  function hide(fig) {
    fig.classList.remove("is-active");
    var tip = fig.querySelector(".wchart-tip");
    if (tip) tip.hidden = true;
  }

  document.querySelectorAll("[data-chart]").forEach(function (fig) {
    draw(fig);
    fig.addEventListener("pointermove", function (e) { if (fig._show) fig._show(fig._nearest(e.clientX)); });
    fig.addEventListener("pointerdown", function (e) { if (fig._show) fig._show(fig._nearest(e.clientX)); });
    fig.addEventListener("pointerleave", function () { if (document.activeElement !== fig) hide(fig); });
    fig.addEventListener("focus", function () { if (fig._show) fig._show(fig._count - 1); });
    fig.addEventListener("blur", function () { hide(fig); });
    fig.addEventListener("keydown", function (e) {
      if (!fig._show || (e.key !== "ArrowLeft" && e.key !== "ArrowRight")) return;
      e.preventDefault();
      var later = (e.key === "ArrowRight") !== fig._rtl;
      fig._show(Math.min(Math.max((fig._index || 0) + (later ? 1 : -1), 0), fig._count - 1));
    });
    if (window.ResizeObserver) {
      var width = fig.clientWidth;
      new ResizeObserver(function () {
        if (fig.clientWidth !== width) { width = fig.clientWidth; hide(fig); draw(fig); }
      }).observe(fig);
    }
  });
})();
