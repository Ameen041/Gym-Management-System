/* Himma exercise animations: a flat figure seen from the side does the movement, and the muscles it
 * works light up (main muscles red, helping ones light red).
 *
 * <svg data-motion="barbell-back-squat" data-primary="quads glutes" data-secondary="hamstrings"></svg>
 *
 * A motion is a list of poses. A pose gives the direction of each body part, in degrees:
 * 0 = pointing down, 90 = forward (the figure faces right), 180 = up, -90 = back.
 * The figure is built from the hip outwards, then moved so that its anchor (feet on the floor,
 * back on the bench...) stays where it is. Poses are eased into each other and the cycle loops.
 */
(function () {
  "use strict";

  var NS = "http://www.w3.org/2000/svg";
  var LEN = { torso: 50, neck: 9, upperArm: 28, forearm: 26, thigh: 37, shin: 36, foot: 13 };
  var HEAD_R = 10.5;

  // Standing, arms down: every motion starts from this and changes what it needs
  var BASE = {
    torso: 180, head: 180,
    uaN: 0, faN: 0, uaF: 0, faF: 0,
    thN: 0, shN: 0, ftN: 90, thF: 0, shF: 0, ftF: 90
  };

  function pose(changes) {
    var p = { bar0: 0, bar1: 0 };
    for (var k in BASE) p[k] = BASE[k];
    for (var c in changes) {
      if (c === "bar") { p.bar0 = changes.bar[0]; p.bar1 = changes.bar[1]; } else p[c] = changes[c];
    }
    return p;
  }

  /* Each motion: anchor (joint kept in place), poses, timing (seconds to go from one pose to the next,
   * and to hold it), props (equipment) and the floor/bench. */
  var MOTIONS = {
    // Back squat: bar on the upper back, sit down and back, chest up, knees over the toes
    "barbell-back-squat": {
      anchor: ["ankleN", 112, 178],
      ik: { bend: -1 },           // hands stay on the bar, elbows point back and down
      bar: "shoulder",            // the bar moves with the shoulders
      poses: [
        pose({ bar: [-11, 3] }),
        pose({ torso: 140, head: 168, thN: 86, shN: -33, thF: 86, shF: -33, bar: [-11, 3] })
      ],
      timing: [[1.5, 0.35], [1.15, 0.7]],
      scene: [{ type: "floor", y: 184 }]
    },
    // Bench press: lying on a flat bench, lower the bar to the middle of the chest, press it up
    "barbell-bench-press": {
      anchor: ["hip", 132, 125],
      depth: [1.5, -2.5],
      ik: { bend: 1 },            // elbows go down, towards the floor
      bar: "shoulder",
      poses: [
        pose({ torso: -90, head: -90, thN: 62, shN: -4, thF: 54, shF: -8, bar: [5, -55] }),
        pose({ torso: -90, head: -90, thN: 62, shN: -4, thF: 54, shF: -8, bar: [16, -17] })
      ],
      timing: [[1.4, 0.3], [1.0, 0.6]],
      scene: [{ type: "bench", x: 62, y: 138, w: 90, h: 7, legs: [72, 142] }, { type: "floor", y: 184 }]
    },
    // Biceps curl: elbows stay by the sides, curl the bar up to the shoulders and lower it slowly
    "barbell-biceps-curl": {
      anchor: ["ankleN", 104, 178],
      bar: "hand",
      poses: [
        pose({ uaN: 4, faN: 10, uaF: 4, faF: 10 }),
        pose({ uaN: 14, faN: 158, uaF: 14, faF: 158 })
      ],
      timing: [[1.0, 0.25], [1.6, 0.5]],
      scene: [{ type: "floor", y: 184 }]
    }
  };

  // Where each muscle sits on the figure: [part, from, to (along it), side (1 front / -1 back), width share]
  var MUSCLES = {
    quads:       [["thigh", 0.18, 0.86, 1, 0.62]],
    hamstrings:  [["thigh", 0.2, 0.86, -1, 0.56]],
    glutes:      [["hip"]],
    calves:      [["shin", 0.12, 0.6, -1, 0.66]],
    chest:       [["torso", 0.64, 0.9, -1, 0.5]],
    abs:         [["torso", 0.16, 0.6, -1, 0.42]],
    obliques:    [["torso", 0.14, 0.56, -1, 0.28]],
    lower_back:  [["torso", 0.1, 0.46, 1, 0.38]],
    middle_back: [["torso", 0.55, 0.85, 1, 0.42]],
    lats:        [["torso", 0.38, 0.82, 1, 0.5]],
    traps:       [["torso", 0.84, 1.0, 1, 0.46]],
    shoulders:   [["shoulder"]],
    biceps:      [["upperArm", 0.2, 0.84, 1, 0.62]],
    triceps:     [["upperArm", 0.2, 0.86, -1, 0.6]],
    forearms:    [["forearm", 0.08, 0.6, 1, 0.75]],
    adductors:   [["thigh", 0.2, 0.7, 1, 0.3]],
    abductors:   [["hip"]]
  };

  function vec(deg) {
    var r = deg * Math.PI / 180;
    return [Math.sin(r), Math.cos(r)];
  }
  function add(p, d, len) { return [p[0] + d[0] * len, p[1] + d[1] * len]; }

  // Two-bone arm: where the elbow goes so the hand reaches the target (bend: which side the elbow points)
  function reach(shoulder, target, bend) {
    var dx = target[0] - shoulder[0], dy = target[1] - shoulder[1];
    var a = LEN.upperArm, b = LEN.forearm;
    var d = Math.min(Math.hypot(dx, dy), a + b - 0.01);
    var base = Math.atan2(dy, dx);
    var turn = Math.acos(Math.max(-1, Math.min(1, (a * a + d * d - b * b) / (2 * a * d))));
    var ang = base + bend * turn;
    var elbow = [shoulder[0] + Math.cos(ang) * a, shoulder[1] + Math.sin(ang) * a];
    var hand = [shoulder[0] + Math.cos(base) * d, shoulder[1] + Math.sin(base) * d];
    return [elbow, hand];
  }

  // Joint positions for a pose, before anchoring (hip at 0,0)
  function joints(p, motion) {
    var j = { hip: [0, 0] };
    j.shoulder = add(j.hip, vec(p.torso), LEN.torso);
    j.neck = add(j.shoulder, vec(p.head), LEN.neck);
    j.head = add(j.neck, vec(p.head), HEAD_R * 0.9);
    ["N", "F"].forEach(function (s) {
      j["knee" + s] = add(j.hip, vec(p["th" + s]), LEN.thigh);
      j["ankle" + s] = add(j["knee" + s], vec(p["sh" + s]), LEN.shin);
      j["toe" + s] = add(j["ankle" + s], vec(p["ft" + s]), LEN.foot);
    });
    if (motion.bar === "shoulder") j.bar = [j.shoulder[0] + p.bar0, j.shoulder[1] + p.bar1];
    ["N", "F"].forEach(function (s) {
      if (motion.ik && j.bar) {
        var arm = reach(j.shoulder, j.bar, motion.ik.bend);
        j["elbow" + s] = arm[0];
        j["hand" + s] = arm[1];
      } else {
        j["elbow" + s] = add(j.shoulder, vec(p["ua" + s]), LEN.upperArm);
        j["hand" + s] = add(j["elbow" + s], vec(p["fa" + s]), LEN.forearm);
      }
    });
    if (motion.bar === "hand") j.bar = j.handN;
    return j;
  }

  function ease(t) { return 0.5 - Math.cos(Math.PI * t) / 2; }
  function mix(a, b, t) {
    var out = {};
    for (var k in a) out[k] = a[k] + (b[k] - a[k]) * t;
    return out;
  }

  // Pose at a time (seconds) in the loop, and how hard the muscles work (1 while lifting)
  function at(motion, time) {
    var steps = motion.timing, total = 0, i;
    for (i = 0; i < steps.length; i++) total += steps[i][0] + steps[i][1];
    var t = time % total;
    for (i = 0; i < steps.length; i++) {
      var move = steps[i][0], hold = steps[i][1];
      var a = motion.poses[i], b = motion.poses[(i + 1) % motion.poses.length];
      if (t < move) return { pose: mix(a, b, ease(t / move)), effort: i === steps.length - 1 ? 1 : 0.55 };
      t -= move;
      if (t < hold) return { pose: b, effort: i === steps.length - 1 ? 0.45 : 0.8 };
      t -= hold;
    }
    return { pose: motion.poses[0], effort: 0.5 };
  }

  function el(name, attrs, parent) {
    var n = document.createElementNS(NS, name);
    for (var k in attrs) n.setAttribute(k, attrs[k]);
    if (parent) parent.appendChild(n);
    return n;
  }

  // A limb or the torso: a rounded band from a to b, w1 wide at a and w2 at b
  function band(a, b, w1, w2) {
    var dx = b[0] - a[0], dy = b[1] - a[1], l = Math.hypot(dx, dy) || 1;
    var nx = -dy / l, ny = dx / l, r1 = w1 / 2, r2 = w2 / 2;
    var p = function (x, y) { return x.toFixed(2) + " " + y.toFixed(2); };
    return "M" + p(a[0] + nx * r1, a[1] + ny * r1) +
      "L" + p(b[0] + nx * r2, b[1] + ny * r2) +
      "A" + r2 + " " + r2 + " 0 0 0 " + p(b[0] - nx * r2, b[1] - ny * r2) +
      "L" + p(a[0] - nx * r1, a[1] - ny * r1) +
      "A" + r1 + " " + r1 + " 0 0 0 " + p(a[0] + nx * r1, a[1] + ny * r1) + "Z";
  }

  var WIDTH = { torso: [30, 25], upperArm: [12, 10], forearm: [10, 8], thigh: [19, 13], shin: [12.5, 9], foot: [8, 6] };

  function Figure(svg) {
    this.svg = svg;
    this.motion = MOTIONS[svg.getAttribute("data-motion")];
    this.primary = (svg.getAttribute("data-primary") || "").split(/\s+/).filter(Boolean);
    this.secondary = (svg.getAttribute("data-secondary") || "").split(/\s+/).filter(Boolean);
    svg.setAttribute("viewBox", "0 0 220 200");
    this.scene = el("g", {}, svg);
    this.body = el("g", {}, svg);
    this.front = el("g", {}, svg);
    var self = this;
    (this.motion.scene || []).forEach(function (s) { self.drawScene(s); });
  }

  Figure.prototype.drawScene = function (s) {
    if (s.type === "floor") el("rect", { x: 0, y: s.y, width: 220, height: 2.5, rx: 1.25, "class": "em-floor" }, this.scene);
    if (s.type === "bench") {
      s.legs.forEach(function (x) { el("rect", { x: x - 2.5, y: s.y + s.h, width: 5, height: 184 - s.y - s.h, "class": "em-bench-leg" }, this.scene); }, this);
      el("rect", { x: s.x, y: s.y, width: s.w, height: s.h, rx: 3, "class": "em-bench" }, this.scene);
    }
  };

  Figure.prototype.draw = function (state) {
    var m = this.motion, j = joints(state.pose, m);
    var anchor = j[m.anchor[0]], ox = m.anchor[1] - anchor[0], oy = m.anchor[2] - anchor[1];
    for (var k in j) j[k] = [j[k][0] + ox, j[k][1] + oy];
    // the far arm and leg sit a little behind and above the near ones, so both show
    var depth = m.depth || [3.5, -1.5];
    ["elbowF", "handF", "kneeF", "ankleF", "toeF"].forEach(function (n) { j[n] = [j[n][0] + depth[0], j[n][1] + depth[1]]; });
    j.shoulderF = [j.shoulder[0] + depth[0], j.shoulder[1] + depth[1]];
    j.hipF = [j.hip[0] + depth[0], j.hip[1] + depth[1]];

    this.body.textContent = "";
    this.front.textContent = "";
    var parts = {}; // where each body part was drawn, for the muscles
    var self = this;

    function limb(group, name, from, to, cls, side) {
      el("path", { d: band(j[from], j[to], WIDTH[name][0], WIDTH[name][1]), "class": cls }, group);
      parts[name + side] = [j[from], j[to], WIDTH[name]];
    }

    // far side first (darker), then the torso and head, then the near side
    limb(this.body, "upperArm", "shoulderF", "elbowF", "em-far", "F");
    limb(this.body, "forearm", "elbowF", "handF", "em-far", "F");
    limb(this.body, "thigh", "hipF", "kneeF", "em-far", "F");
    limb(this.body, "shin", "kneeF", "ankleF", "em-far", "F");
    limb(this.body, "foot", "ankleF", "toeF", "em-far", "F");
    this.muscles(parts, "F", this.body, j, true);

    el("path", { d: band(j.hip, j.shoulder, WIDTH.torso[1], WIDTH.torso[0]), "class": "em-skin" }, this.body);
    parts.torso = [j.hip, j.shoulder, [WIDTH.torso[1], WIDTH.torso[0]]];
    el("path", { d: band(j.shoulder, j.neck, 10, 9), "class": "em-skin" }, this.body);
    el("circle", { cx: j.head[0], cy: j.head[1], r: HEAD_R, "class": "em-skin" }, this.body);
    // hair on the back and top of the head shows which way the figure faces
    var up = vec(state.pose.head), back = vec(state.pose.head - 90), c = j.head, r = HEAD_R;
    var p1 = [c[0] + vec(state.pose.head + 35)[0] * r, c[1] + vec(state.pose.head + 35)[1] * r];
    var p2 = [c[0] + vec(state.pose.head - 115)[0] * r, c[1] + vec(state.pose.head - 115)[1] * r];
    el("path", { d: "M" + p1[0].toFixed(2) + " " + p1[1].toFixed(2) + "A" + r + " " + r + " 0 0 0 " + p2[0].toFixed(2) + " " + p2[1].toFixed(2) +
      "Q" + (c[0] + back[0] * 2).toFixed(2) + " " + (c[1] + back[1] * 2 + up[1] * 0).toFixed(2) + " " + p1[0].toFixed(2) + " " + p1[1].toFixed(2) + "Z", "class": "em-hair" }, this.body);
    this.muscles(parts, "torso", this.body, j, false);

    limb(this.front, "thigh", "hip", "kneeN", "em-skin", "N");
    limb(this.front, "shin", "kneeN", "ankleN", "em-skin", "N");
    limb(this.front, "foot", "ankleN", "toeN", "em-skin", "N");
    this.muscles(parts, "N", this.front, j, false, "legs");
    // the plate sits behind the near arm, so the arm and its muscles stay in sight
    if (j.bar) this.drawBar(j.bar);
    limb(this.front, "upperArm", "shoulder", "elbowN", "em-skin", "N");
    limb(this.front, "forearm", "elbowN", "handN", "em-skin", "N");
    el("circle", { cx: j.handN[0], cy: j.handN[1], r: 5, "class": "em-skin" }, this.front);
    this.muscles(parts, "N", this.front, j, false, "arms");

    var effort = "--effort:" + state.effort.toFixed(2);
    this.front.setAttribute("style", effort);
    this.body.setAttribute("style", effort);
  };

  Figure.prototype.drawBar = function (at) {
    el("circle", { cx: at[0], cy: at[1], r: 15, "class": "em-plate" }, this.front);
    el("circle", { cx: at[0], cy: at[1], r: 9.5, "class": "em-plate-ring" }, this.front);
    el("circle", { cx: at[0], cy: at[1], r: 3, "class": "em-bar" }, this.front);
  };

  // which muscles to draw in this layer
  var LAYER = {
    torso: ["chest", "abs", "obliques", "lower_back", "middle_back", "lats", "traps", "glutes", "abductors"],
    legs: ["quads", "hamstrings", "calves", "adductors"],
    arms: ["biceps", "triceps", "forearms", "shoulders"]
  };

  Figure.prototype.muscles = function (parts, side, group, j, far, only) {
    var self = this;
    var list = side === "torso" ? LAYER.torso : side === "F" ? LAYER.legs.concat(LAYER.arms) : LAYER[only];
    list.forEach(function (key) {
      var level = self.primary.indexOf(key) >= 0 ? "em-on" : self.secondary.indexOf(key) >= 0 ? "em-soft" : null;
      if (!level) return;
      MUSCLES[key].forEach(function (spot) {
        var cls = "em-muscle " + level + (far ? " em-muscle-far" : "");
        if (spot[0] === "hip") {
          if (far || side !== "torso") return;
          // glutes sit behind the hip, on the side away from the belly
          var t = parts.torso, dx = t[1][0] - t[0][0], dy = t[1][1] - t[0][1], l = Math.hypot(dx, dy);
          var bx = dy / l, by = -dx / l; // back of the body
          el("ellipse", { cx: j.hip[0] + bx * 8, cy: j.hip[1] + by * 8 + 2, rx: 10, ry: 9, "class": cls }, group);
          return;
        }
        if (spot[0] === "shoulder") {
          if (far) return;
          el("circle", { cx: j.shoulder[0], cy: j.shoulder[1] + 1, r: 8, "class": cls }, group);
          return;
        }
        var part = parts[spot[0] === "torso" ? "torso" : spot[0] + side];
        if (!part) return;
        var a = part[0], b = part[1], w = part[2];
        var dx2 = b[0] - a[0], dy2 = b[1] - a[1], l2 = Math.hypot(dx2, dy2);
        // the "front" of a limb is to the facing side when it hangs down
        var nx = dy2 / l2, ny = -dx2 / l2;
        var width = (w[0] + w[1]) / 2;
        var off = spot[3] * width * (0.5 - spot[4] / 2);
        var p1 = [a[0] + dx2 * spot[1] + nx * off, a[1] + dy2 * spot[1] + ny * off];
        var p2 = [a[0] + dx2 * spot[2] + nx * off, a[1] + dy2 * spot[2] + ny * off];
        el("path", { d: band(p1, p2, width * spot[4], width * spot[4] * 0.85), "class": cls }, group);
      });
    });
  };

  function start(svg) {
    if (!MOTIONS[svg.getAttribute("data-motion")]) return;
    var fig = new Figure(svg);
    var still = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    var playing = !still, began = null, shown = 0;
    var box = svg.closest("figure") || svg.parentNode;
    var btn = box.querySelector("[data-motion-play]");

    function frame(now) {
      if (began === null) began = now - shown * 1000;
      if (playing) shown = (now - began) / 1000;
      fig.draw(at(fig.motion, shown));
      if (playing) requestAnimationFrame(frame);
    }
    fig.draw(at(fig.motion, 0));
    if (playing) requestAnimationFrame(frame);

    if (btn) {
      var label = btn.querySelector("span"), icon = btn.querySelector("i");
      var texts = [btn.getAttribute("data-play-text"), label.textContent];
      var sync = function () {
        icon.className = playing ? "fas fa-pause" : "fas fa-play";
        label.textContent = playing ? texts[1] : texts[0];
        btn.setAttribute("aria-pressed", playing ? "true" : "false");
      };
      sync();
      btn.addEventListener("click", function () {
        playing = !playing;
        began = null;
        sync();
        if (playing) requestAnimationFrame(frame);
      });
    }
    // a frame for tests and screenshots: ?t=1.2 on the svg element
    svg.drawAt = function (seconds) { playing = false; shown = seconds; fig.draw(at(fig.motion, seconds)); };
  }

  window.HimmaMotions = MOTIONS;
  function init() { document.querySelectorAll("svg[data-motion]").forEach(start); }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init); else init();
})();
