try{
	var args = process.argv;
	$ = {};
	$.username = args.pop();
	$.password = args.pop();
	$.verifycode = args.pop();
	$.verifysession = args.pop();
	$.login_sig = args.pop();
	$.salt = args.pop();

	var pt = {};
	pt.str = {
		no_uin: "您还没有输入帐号！",
		no_pwd: "您还没有输入密码！",
		no_vcode: "您还没有输入验证码！",
		inv_uin: "请输入正确的帐号！",
		inv_vcode: "请输入完整的验证码！",
		qlogin_expire: "您所选择号码对应的QQ已经失效，请检查该号码对应的QQ是否已经被关闭。",
		other_login: "帐号登录",
		h_pt_login: "帐号密码登录",
		otherqq_login: "QQ帐号密码登录"
	};

	pt.ptui = {
		target: isNaN(parseInt("1")) ? {
			_top: 1,
			_self: 0,
			_parent: 2
		}["1"] : parseInt("1"),
		s_url: "http\x3A\x2F\x2Fe.qq.com\x2Findex.shtml\x3Frefer\x3Dhttp\x3A\x2F\x2Fe.qq.com\x2Fatlas\x2F1004770\x2F",
		lang: encodeURIComponent("2052"),
		ptui_version: encodeURIComponent("10148"),
		login_sig: $.login_sig,
		style: encodeURIComponent("20"),
		low_login: encodeURIComponent("0"),
		csimc: encodeURIComponent("0"),
		csnum: encodeURIComponent("0"),
		authid: encodeURIComponent("0"),
		appid: encodeURIComponent("15000103"),
		daid: encodeURIComponent(""),
		pt_3rd_aid: encodeURIComponent("0"),
		regmaster: encodeURIComponent(""),
		mibao_css: encodeURIComponent(""),
		pt_qzone_sig: "0",
		pt_light: "0",


		proxy_url: "",
		jumpname: encodeURIComponent(""),
		defaultUin: "",
		lockuin: parseInt("0"),
		href: "http\x3A\x2F\x2Fxui.ptlogin2.qq.com\x2Fcgi-bin\x2Fxlogin\x3Fappid\x3D15000103\x26s_url\x3Dhttp\x253A\x252F\x252Fe.qq.com\x252Findex.shtml\x253Frefer\x253Dhttp\x253A\x252F\x252Fe.qq.com\x252Fatlas\x252F1004770\x252F\x26style\x3D20\x26border_radius\x3D1\x26target\x3Dtop\x26maskOpacity\x3D40\x26",

		clientip: "",
		serverip: "",
		version: "201203081004",
		isHttps: !1,
		cssPath: "http://imgcache.qq.com/ptlogin/v4/style/20",
		domain: encodeURIComponent("qq.com"),
		fromStyle: parseInt(""),
		enable_qlogin: "1",
		noAuth: "0",
		
		auth_mode: encodeURIComponent("0"),
		pt_vcode_v1: "1",
		pt_ver_md5: "000D1DCFD7BAB2FC0FC8C6481847B548E94657317D130B439668647B",
		gzipEnable: "1"
	};


	$.RSA = function() {
		function t(t, e) {
			return new p(t, e)
		}
		function e(t, e) {
			if (e < t.length + 11) return uv_alert("Message too long for RSA"), null;
			for (var i = new Array, n = t.length - 1; n >= 0 && e > 0;) {
				var o = t.charCodeAt(n--);
				i[--e] = o
			}
			i[--e] = 0;
			for (var r = new Y, a = new Array; e > 2;) {
				for (a[0] = 0; 0 == a[0];) r.nextBytes(a);
				i[--e] = a[0]
			}
			return i[--e] = 2, i[--e] = 0, new p(i)
		}
		function i() {
			this.n = null, this.e = 0, this.d = null, this.p = null, this.q = null, this.dmp1 = null, this.dmq1 = null, this.coeff = null
		}
		function n(e, i) {
			null != e && null != i && e.length > 0 && i.length > 0 ? (this.n = t(e, 16), this.e = parseInt(i, 16)) : uv_alert("Invalid RSA public key")
		}
		function o(t) {
			return t.modPowInt(this.e, this.n)
		}
		function r(t) {
			var i = e(t, this.n.bitLength() + 7 >> 3);
			if (null == i) return null;
			var n = this.doPublic(i);
			if (null == n) return null;
			var o = n.toString(16);
			return 0 == (1 & o.length) ? o : "0" + o
		}
		function p(t, e, i) {
			null != t && ("number" == typeof t ? this.fromNumber(t, e, i) : null == e && "string" != typeof t ? this.fromString(t, 256) : this.fromString(t, e))
		}
		function a() {
			return new p(null)
		}
		function s(t, e, i, n, o, r) {
			for (; --r >= 0;) {
				var p = e * this[t++] + i[n] + o;
				o = Math.floor(p / 67108864), i[n++] = 67108863 & p
			}
			return o
		}
		function l(t, e, i, n, o, r) {
			for (var p = 32767 & e, a = e >> 15; --r >= 0;) {
				var s = 32767 & this[t],
					l = this[t++] >> 15,
					c = a * s + l * p;
				s = p * s + ((32767 & c) << 15) + i[n] + (1073741823 & o), o = (s >>> 30) + (c >>> 15) + a * l + (o >>> 30), i[n++] = 1073741823 & s
			}
			return o
		}
		function c(t, e, i, n, o, r) {
			for (var p = 16383 & e, a = e >> 14; --r >= 0;) {
				var s = 16383 & this[t],
					l = this[t++] >> 14,
					c = a * s + l * p;
				s = p * s + ((16383 & c) << 14) + i[n] + o, o = (s >> 28) + (c >> 14) + a * l, i[n++] = 268435455 & s
			}
			return o
		}
		function u(t) {
			return ut.charAt(t)
		}
		function g(t, e) {
			var i = gt[t.charCodeAt(e)];
			return null == i ? -1 : i
		}
		function d(t) {
			for (var e = this.t - 1; e >= 0; --e) t[e] = this[e];
			t.t = this.t, t.s = this.s
		}
		function h(t) {
			this.t = 1, this.s = 0 > t ? -1 : 0, t > 0 ? this[0] = t : -1 > t ? this[0] = t + DV : this.t = 0
		}
		function m(t) {
			var e = a();
			return e.fromInt(t), e
		}
		function f(t, e) {
			var i;
			if (16 == e) i = 4;
			else if (8 == e) i = 3;
			else if (256 == e) i = 8;
			else if (2 == e) i = 1;
			else if (32 == e) i = 5;
			else {
				if (4 != e) return void this.fromRadix(t, e);
				i = 2
			}
			this.t = 0, this.s = 0;
			for (var n = t.length, o = !1, r = 0; --n >= 0;) {
				var a = 8 == i ? 255 & t[n] : g(t, n);
				0 > a ? "-" == t.charAt(n) && (o = !0) : (o = !1, 0 == r ? this[this.t++] = a : r + i > this.DB ? (this[this.t - 1] |= (a & (1 << this.DB - r) - 1) << r, this[this.t++] = a >> this.DB - r) : this[this.t - 1] |= a << r, r += i, r >= this.DB && (r -= this.DB))
			}
			8 == i && 0 != (128 & t[0]) && (this.s = -1, r > 0 && (this[this.t - 1] |= (1 << this.DB - r) - 1 << r)), this.clamp(), o && p.ZERO.subTo(this, this)
		}
		function _() {
			for (var t = this.s & this.DM; this.t > 0 && this[this.t - 1] == t;)--this.t
		}
		function v(t) {
			if (this.s < 0) return "-" + this.negate().toString(t);
			var e;
			if (16 == t) e = 4;
			else if (8 == t) e = 3;
			else if (2 == t) e = 1;
			else if (32 == t) e = 5;
			else {
				if (4 != t) return this.toRadix(t);
				e = 2
			}
			var i, n = (1 << e) - 1,
				o = !1,
				r = "",
				p = this.t,
				a = this.DB - p * this.DB % e;
			if (p-- > 0) for (a < this.DB && (i = this[p] >> a) > 0 && (o = !0, r = u(i)); p >= 0;) e > a ? (i = (this[p] & (1 << a) - 1) << e - a, i |= this[--p] >> (a += this.DB - e)) : (i = this[p] >> (a -= e) & n, 0 >= a && (a += this.DB, --p)), i > 0 && (o = !0), o && (r += u(i));
			return o ? r : "0"
		}
		function $() {
			var t = a();
			return p.ZERO.subTo(this, t), t
		}
		function w() {
			return this.s < 0 ? this.negate() : this
		}
		function y(t) {
			var e = this.s - t.s;
			if (0 != e) return e;
			var i = this.t;
			if (e = i - t.t, 0 != e) return e;
			for (; --i >= 0;) if (0 != (e = this[i] - t[i])) return e;
			return 0
		}
		function k(t) {
			var e, i = 1;
			return 0 != (e = t >>> 16) && (t = e, i += 16), 0 != (e = t >> 8) && (t = e, i += 8), 0 != (e = t >> 4) && (t = e, i += 4), 0 != (e = t >> 2) && (t = e, i += 2), 0 != (e = t >> 1) && (t = e, i += 1), i
		}
		function b() {
			return this.t <= 0 ? 0 : this.DB * (this.t - 1) + k(this[this.t - 1] ^ this.s & this.DM)
		}
		function q(t, e) {
			var i;
			for (i = this.t - 1; i >= 0; --i) e[i + t] = this[i];
			for (i = t - 1; i >= 0; --i) e[i] = 0;
			e.t = this.t + t, e.s = this.s
		}
		function C(t, e) {
			for (var i = t; i < this.t; ++i) e[i - t] = this[i];
			e.t = Math.max(this.t - t, 0), e.s = this.s
		}
		function S(t, e) {
			var i, n = t % this.DB,
				o = this.DB - n,
				r = (1 << o) - 1,
				p = Math.floor(t / this.DB),
				a = this.s << n & this.DM;
			for (i = this.t - 1; i >= 0; --i) e[i + p + 1] = this[i] >> o | a, a = (this[i] & r) << n;
			for (i = p - 1; i >= 0; --i) e[i] = 0;
			e[p] = a, e.t = this.t + p + 1, e.s = this.s, e.clamp()
		}
		function T(t, e) {
			e.s = this.s;
			var i = Math.floor(t / this.DB);
			if (i >= this.t) return void(e.t = 0);
			var n = t % this.DB,
				o = this.DB - n,
				r = (1 << n) - 1;
			e[0] = this[i] >> n;
			for (var p = i + 1; p < this.t; ++p) e[p - i - 1] |= (this[p] & r) << o, e[p - i] = this[p] >> n;
			n > 0 && (e[this.t - i - 1] |= (this.s & r) << o), e.t = this.t - i, e.clamp()
		}
		function x(t, e) {
			for (var i = 0, n = 0, o = Math.min(t.t, this.t); o > i;) n += this[i] - t[i], e[i++] = n & this.DM, n >>= this.DB;
			if (t.t < this.t) {
				for (n -= t.s; i < this.t;) n += this[i], e[i++] = n & this.DM, n >>= this.DB;
				n += this.s
			} else {
				for (n += this.s; i < t.t;) n -= t[i], e[i++] = n & this.DM, n >>= this.DB;
				n -= t.s
			}
			e.s = 0 > n ? -1 : 0, -1 > n ? e[i++] = this.DV + n : n > 0 && (e[i++] = n), e.t = i, e.clamp()
		}
		function A(t, e) {
			var i = this.abs(),
				n = t.abs(),
				o = i.t;
			for (e.t = o + n.t; --o >= 0;) e[o] = 0;
			for (o = 0; o < n.t; ++o) e[o + i.t] = i.am(0, n[o], e, o, 0, i.t);
			e.s = 0, e.clamp(), this.s != t.s && p.ZERO.subTo(e, e)
		}
		function E(t) {
			for (var e = this.abs(), i = t.t = 2 * e.t; --i >= 0;) t[i] = 0;
			for (i = 0; i < e.t - 1; ++i) {
				var n = e.am(i, e[i], t, 2 * i, 0, 1);
				(t[i + e.t] += e.am(i + 1, 2 * e[i], t, 2 * i + 1, n, e.t - i - 1)) >= e.DV && (t[i + e.t] -= e.DV, t[i + e.t + 1] = 1)
			}
			t.t > 0 && (t[t.t - 1] += e.am(i, e[i], t, 2 * i, 0, 1)), t.s = 0, t.clamp()
		}
		function N(t, e, i) {
			var n = t.abs();
			if (!(n.t <= 0)) {
				var o = this.abs();
				if (o.t < n.t) return null != e && e.fromInt(0), void(null != i && this.copyTo(i));
				null == i && (i = a());
				var r = a(),
					s = this.s,
					l = t.s,
					c = this.DB - k(n[n.t - 1]);
				c > 0 ? (n.lShiftTo(c, r), o.lShiftTo(c, i)) : (n.copyTo(r), o.copyTo(i));
				var u = r.t,
					g = r[u - 1];
				if (0 != g) {
					var d = g * (1 << this.F1) + (u > 1 ? r[u - 2] >> this.F2 : 0),
						h = this.FV / d,
						m = (1 << this.F1) / d,
						f = 1 << this.F2,
						_ = i.t,
						v = _ - u,
						$ = null == e ? a() : e;
					for (r.dlShiftTo(v, $), i.compareTo($) >= 0 && (i[i.t++] = 1, i.subTo($, i)), p.ONE.dlShiftTo(u, $), $.subTo(r, r); r.t < u;) r[r.t++] = 0;
					for (; --v >= 0;) {
						var w = i[--_] == g ? this.DM : Math.floor(i[_] * h + (i[_ - 1] + f) * m);
						if ((i[_] += r.am(0, w, i, v, 0, u)) < w) for (r.dlShiftTo(v, $), i.subTo($, i); i[_] < --w;) i.subTo($, i)
					}
					null != e && (i.drShiftTo(u, e), s != l && p.ZERO.subTo(e, e)), i.t = u, i.clamp(), c > 0 && i.rShiftTo(c, i), 0 > s && p.ZERO.subTo(i, i)
				}
			}
		}
		function L(t) {
			var e = a();
			return this.abs().divRemTo(t, null, e), this.s < 0 && e.compareTo(p.ZERO) > 0 && t.subTo(e, e), e
		}
		function B(t) {
			this.m = t
		}
		function P(t) {
			return t.s < 0 || t.compareTo(this.m) >= 0 ? t.mod(this.m) : t
		}
		function D(t) {
			return t
		}
		function I(t) {
			t.divRemTo(this.m, null, t)
		}
		function M(t, e, i) {
			t.multiplyTo(e, i), this.reduce(i)
		}
		function U(t, e) {
			t.squareTo(e), this.reduce(e)
		}
		function H() {
			if (this.t < 1) return 0;
			var t = this[0];
			if (0 == (1 & t)) return 0;
			var e = 3 & t;
			return e = e * (2 - (15 & t) * e) & 15, e = e * (2 - (255 & t) * e) & 255, e = e * (2 - ((65535 & t) * e & 65535)) & 65535, e = e * (2 - t * e % this.DV) % this.DV, e > 0 ? this.DV - e : -e
		}
		function Q(t) {
			this.m = t, this.mp = t.invDigit(), this.mpl = 32767 & this.mp, this.mph = this.mp >> 15, this.um = (1 << t.DB - 15) - 1, this.mt2 = 2 * t.t
		}
		function j(t) {
			var e = a();
			return t.abs().dlShiftTo(this.m.t, e), e.divRemTo(this.m, null, e), t.s < 0 && e.compareTo(p.ZERO) > 0 && this.m.subTo(e, e), e
		}
		function V(t) {
			var e = a();
			return t.copyTo(e), this.reduce(e), e
		}
		function O(t) {
			for (; t.t <= this.mt2;) t[t.t++] = 0;
			for (var e = 0; e < this.m.t; ++e) {
				var i = 32767 & t[e],
					n = i * this.mpl + ((i * this.mph + (t[e] >> 15) * this.mpl & this.um) << 15) & t.DM;
				for (i = e + this.m.t, t[i] += this.m.am(0, n, t, e, 0, this.m.t); t[i] >= t.DV;) t[i] -= t.DV, t[++i]++
			}
			t.clamp(), t.drShiftTo(this.m.t, t), t.compareTo(this.m) >= 0 && t.subTo(this.m, t)
		}
		function R(t, e) {
			t.squareTo(e), this.reduce(e)
		}
		function F(t, e, i) {
			t.multiplyTo(e, i), this.reduce(i)
		}
		function z() {
			return 0 == (this.t > 0 ? 1 & this[0] : this.s)
		}
		function G(t, e) {
			if (t > 4294967295 || 1 > t) return p.ONE;
			var i = a(),
				n = a(),
				o = e.convert(this),
				r = k(t) - 1;
			for (o.copyTo(i); --r >= 0;) if (e.sqrTo(i, n), (t & 1 << r) > 0) e.mulTo(n, o, i);
			else {
				var s = i;
				i = n, n = s
			}
			return e.revert(i)
		}
		function X(t, e) {
			var i;
			return i = 256 > t || e.isEven() ? new B(e) : new Q(e), this.exp(t, i)
		}
		function W(t) {
			ht[mt++] ^= 255 & t, ht[mt++] ^= t >> 8 & 255, ht[mt++] ^= t >> 16 & 255, ht[mt++] ^= t >> 24 & 255, mt >= vt && (mt -= vt)
		}
		function Z() {
			W((new Date).getTime())
		}
		function K() {
			if (null == dt) {
				for (Z(), dt = nt(), dt.init(ht), mt = 0; mt < ht.length; ++mt) ht[mt] = 0;
				mt = 0
			}
			return dt.next()
		}
		function J(t) {
			var e;
			for (e = 0; e < t.length; ++e) t[e] = K()
		}
		function Y() {}
		function tt() {
			this.i = 0, this.j = 0, this.S = new Array
		}
		function et(t) {
			var e, i, n;
			for (e = 0; 256 > e; ++e) this.S[e] = e;
			for (i = 0, e = 0; 256 > e; ++e) i = i + this.S[e] + t[e % t.length] & 255, n = this.S[e], this.S[e] = this.S[i], this.S[i] = n;
			this.i = 0, this.j = 0
		}
		function it() {
			var t;
			return this.i = this.i + 1 & 255, this.j = this.j + this.S[this.i] & 255, t = this.S[this.i], this.S[this.i] = this.S[this.j], this.S[this.j] = t, this.S[t + this.S[this.i] & 255]
		}
		function nt() {
			return new tt
		}
		function ot(t, e, n) {
			e = "F20CE00BAE5361F8FA3AE9CEFA495362FF7DA1BA628F64A347F0A8C012BF0B254A30CD92ABFFE7A6EE0DC424CB6166F8819EFA5BCCB20EDFB4AD02E412CCF579B1CA711D55B8B0B3AEB60153D5E0693A2A86F3167D7847A0CB8B00004716A9095D9BADC977CBB804DBDCBA6029A9710869A453F27DFDDF83C016D928B3CBF4C7", n = "3";
			var o = new i;
			return o.setPublic(e, n), o.encrypt(t)
		}
		i.prototype.doPublic = o, i.prototype.setPublic = n, i.prototype.encrypt = r;
		var rt, pt = 0xdeadbeefcafe,
			at = 15715070 == (16777215 & pt);
		(p.prototype.am = c, rt = 28), p.prototype.DB = rt, p.prototype.DM = (1 << rt) - 1, p.prototype.DV = 1 << rt;
		var st = 52;
		p.prototype.FV = Math.pow(2, st), p.prototype.F1 = st - rt, p.prototype.F2 = 2 * rt - st;
		var lt, ct, ut = "0123456789abcdefghijklmnopqrstuvwxyz",
			gt = new Array;
		for (lt = "0".charCodeAt(0), ct = 0; 9 >= ct; ++ct) gt[lt++] = ct;
		for (lt = "a".charCodeAt(0), ct = 10; 36 > ct; ++ct) gt[lt++] = ct;
		for (lt = "A".charCodeAt(0), ct = 10; 36 > ct; ++ct) gt[lt++] = ct;
		B.prototype.convert = P, B.prototype.revert = D, B.prototype.reduce = I, B.prototype.mulTo = M, B.prototype.sqrTo = U, Q.prototype.convert = j, Q.prototype.revert = V, Q.prototype.reduce = O, Q.prototype.mulTo = F, Q.prototype.sqrTo = R, p.prototype.copyTo = d, p.prototype.fromInt = h, p.prototype.fromString = f, p.prototype.clamp = _, p.prototype.dlShiftTo = q, p.prototype.drShiftTo = C, p.prototype.lShiftTo = S, p.prototype.rShiftTo = T, p.prototype.subTo = x, p.prototype.multiplyTo = A, p.prototype.squareTo = E, p.prototype.divRemTo = N, p.prototype.invDigit = H, p.prototype.isEven = z, p.prototype.exp = G, p.prototype.toString = v, p.prototype.negate = $, p.prototype.abs = w, p.prototype.compareTo = y, p.prototype.bitLength = b, p.prototype.mod = L, p.prototype.modPowInt = X, p.ZERO = m(0), p.ONE = m(1);
		var dt, ht, mt;
		if (null == ht) {
			ht = new Array, mt = 0;
			var ft;
			if (typeof crypto!='undefined' && crypto.random) {
				var _t = crypto.random(32);
				for (ft = 0; ft < _t.length; ++ft) ht[mt++] = 255 & _t.charCodeAt(ft)
			}
			for (; vt > mt;) ft = Math.floor(65536 * Math.random()), ht[mt++] = ft >>> 8, ht[mt++] = 255 & ft;
			mt = 0, Z()
		}
		Y.prototype.nextBytes = J, tt.prototype.init = et, tt.prototype.next = it;
		var vt = 256;
		return {
			rsa_encrypt: ot
		}
	}();

	(function(t) {
		function e() {
			return Math.round(4294967295 * Math.random())
		}
		function i(t, e, i) {
			(!i || i > 4) && (i = 4);
			for (var n = 0, o = e; e + i > o; o++) n <<= 8, n |= t[o];
			return (4294967295 & n) >>> 0
		}
		function n(t, e, i) {
			t[e + 3] = i >> 0 & 255, t[e + 2] = i >> 8 & 255, t[e + 1] = i >> 16 & 255, t[e + 0] = i >> 24 & 255
		}
		function o(t) {
			if (!t) return "";
			for (var e = "", i = 0; i < t.length; i++) {
				var n = Number(t[i]).toString(16);
				1 == n.length && (n = "0" + n), e += n
			}
			return e
		}
		function r(t) {
			for (var e = "", i = 0; i < t.length; i += 2) e += String.fromCharCode(parseInt(t.substr(i, 2), 16));
			return e
		}
		function p(t, e) {
			if (!t) return "";
			e && (t = a(t));
			for (var i = [], n = 0; n < t.length; n++) i[n] = t.charCodeAt(n);
			return o(i)
		}
		function a(t) {
			var e, i, n = [],
				o = t.length;
			for (e = 0; o > e; e++) i = t.charCodeAt(e), i > 0 && 127 >= i ? n.push(t.charAt(e)) : i >= 128 && 2047 >= i ? n.push(String.fromCharCode(192 | i >> 6 & 31), String.fromCharCode(128 | 63 & i)) : i >= 2048 && 65535 >= i && n.push(String.fromCharCode(224 | i >> 12 & 15), String.fromCharCode(128 | i >> 6 & 63), String.fromCharCode(128 | 63 & i));
			return n.join("")
		}
		function s(t) {
			_ = new Array(8), v = new Array(8), $ = w = 0, b = !0, f = 0;
			var i = t.length,
				n = 0;
			f = (i + 10) % 8, 0 != f && (f = 8 - f), y = new Array(i + f + 10), _[0] = 255 & (248 & e() | f);
			for (var o = 1; f >= o; o++) _[o] = 255 & e();
			f++;
			for (var o = 0; 8 > o; o++) v[o] = 0;
			for (n = 1; 2 >= n;) 8 > f && (_[f++] = 255 & e(), n++), 8 == f && c();
			for (var o = 0; i > 0;) 8 > f && (_[f++] = t[o++], i--), 8 == f && c();
			for (n = 1; 7 >= n;) 8 > f && (_[f++] = 0, n++), 8 == f && c();
			return y
		}
		function l(t) {
			var e = 0,
				i = new Array(8),
				n = t.length;
			if (k = t, n % 8 != 0 || 16 > n) return null;
			if (v = g(t), f = 7 & v[0], e = n - f - 10, 0 > e) return null;
			for (var o = 0; o < i.length; o++) i[o] = 0;
			y = new Array(e), w = 0, $ = 8, f++;
			for (var r = 1; 2 >= r;) if (8 > f && (f++, r++), 8 == f && (i = t, !d())) return null;
			for (var o = 0; 0 != e;) if (8 > f && (y[o] = 255 & (i[w + f] ^ v[f]), o++, e--, f++), 8 == f && (i = t, w = $ - 8, !d())) return null;
			for (r = 1; 8 > r; r++) {
				if (8 > f) {
					if (0 != (i[w + f] ^ v[f])) return null;
					f++
				}
				if (8 == f && (i = t, w = $, !d())) return null
			}
			return y
		}
		function c() {
			for (var t = 0; 8 > t; t++) _[t] ^= b ? v[t] : y[w + t];
			for (var e = u(_), t = 0; 8 > t; t++) y[$ + t] = e[t] ^ v[t], v[t] = _[t];
			w = $, $ += 8, f = 0, b = !1
		}
		function u(t) {
			for (var e = 16, o = i(t, 0, 4), r = i(t, 4, 4), p = i(m, 0, 4), a = i(m, 4, 4), s = i(m, 8, 4), l = i(m, 12, 4), c = 0, u = 2654435769; e-- > 0;) c += u, c = (4294967295 & c) >>> 0, o += (r << 4) + p ^ r + c ^ (r >>> 5) + a, o = (4294967295 & o) >>> 0, r += (o << 4) + s ^ o + c ^ (o >>> 5) + l, r = (4294967295 & r) >>> 0;
			var g = new Array(8);
			return n(g, 0, o), n(g, 4, r), g
		}
		function g(t) {
			for (var e = 16, o = i(t, 0, 4), r = i(t, 4, 4), p = i(m, 0, 4), a = i(m, 4, 4), s = i(m, 8, 4), l = i(m, 12, 4), c = 3816266640, u = 2654435769; e-- > 0;) r -= (o << 4) + s ^ o + c ^ (o >>> 5) + l, r = (4294967295 & r) >>> 0, o -= (r << 4) + p ^ r + c ^ (r >>> 5) + a, o = (4294967295 & o) >>> 0, c -= u, c = (4294967295 & c) >>> 0;
			var g = new Array(8);
			return n(g, 0, o), n(g, 4, r), g
		}
		function d() {
			for (var t = (k.length, 0); 8 > t; t++) v[t] ^= k[$ + t];
			return v = g(v), $ += 8, f = 0, !0
		}
		function h(t, e) {
			var i = [];
			if (e) for (var n = 0; n < t.length; n++) i[n] = 255 & t.charCodeAt(n);
			else for (var o = 0, n = 0; n < t.length; n += 2) i[o++] = parseInt(t.substr(n, 2), 16);
			return i
		}
		var m = "",
			f = 0,
			_ = [],
			v = [],
			$ = 0,
			w = 0,
			y = [],
			k = [],
			b = !0;
		t.TEA = {
			encrypt: function(t, e) {
				var i = h(t, e),
					n = s(i);
				return o(n)
			},
			enAsBase64: function(t, e) {
				for (var i = h(t, e), n = s(i), o = "", r = 0; r < n.length; r++) o += String.fromCharCode(n[r]);
				return pt.btoa(o)
			},
			decrypt: function(t) {
				var e = h(t, !1),
					i = l(e);
				return o(i)
			},
			initkey: function(t, e) {
				m = h(t, e)
			},
			bytesToStr: r,
			strToBytes: p,
			bytesInStr: o,
			dataFromStr: h
		};
		var q = {};
		q.PADCHAR = "=", q.ALPHA = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/", q.getbyte = function(t, e) {
			var i = t.charCodeAt(e);
			if (i > 255) throw "INVALID_CHARACTER_ERR: DOM Exception 5";
			return i
		}, q.encode = function(t) {
			if (1 != arguments.length) throw "SyntaxError: Not enough arguments";
			var e, i, n = q.PADCHAR,
				o = q.ALPHA,
				r = q.getbyte,
				p = [];
			t = "" + t;
			var a = t.length - t.length % 3;
			if (0 == t.length) return t;
			for (e = 0; a > e; e += 3) i = r(t, e) << 16 | r(t, e + 1) << 8 | r(t, e + 2), p.push(o.charAt(i >> 18)), p.push(o.charAt(i >> 12 & 63)), p.push(o.charAt(i >> 6 & 63)), p.push(o.charAt(63 & i));
			switch (t.length - a) {
			case 1:
				i = r(t, e) << 16, p.push(o.charAt(i >> 18) + o.charAt(i >> 12 & 63) + n + n);
				break;
			case 2:
				i = r(t, e) << 16 | r(t, e + 1) << 8, p.push(o.charAt(i >> 18) + o.charAt(i >> 12 & 63) + o.charAt(i >> 6 & 63) + n)
			}
			return p.join("")
		}, pt.btoa || (pt.btoa = q.encode)
	})(pt);

	$.Encryption = function() {
		function md5(t) {
			return hex_md5(t)
		}
		function hex_md5(t) {
			return binl2hex(core_md5(str2binl(t), t.length * chrsz))
		}
		function str_md5(t) {
			return binl2str(core_md5(str2binl(t), t.length * chrsz))
		}
		function hex_hmac_md5(t, e) {
			return binl2hex(core_hmac_md5(t, e))
		}
		function b64_hmac_md5(t, e) {
			return binl2b64(core_hmac_md5(t, e))
		}
		function str_hmac_md5(t, e) {
			return binl2str(core_hmac_md5(t, e))
		}
		function core_md5(t, e) {
			t[e >> 5] |= 128 << e % 32, t[(e + 64 >>> 9 << 4) + 14] = e;
			for (var i = 1732584193, n = -271733879, o = -1732584194, r = 271733878, p = 0; p < t.length; p += 16) {
				var a = i,
					s = n,
					l = o,
					c = r;
				i = md5_ff(i, n, o, r, t[p + 0], 7, -680876936), r = md5_ff(r, i, n, o, t[p + 1], 12, -389564586), o = md5_ff(o, r, i, n, t[p + 2], 17, 606105819), n = md5_ff(n, o, r, i, t[p + 3], 22, -1044525330), i = md5_ff(i, n, o, r, t[p + 4], 7, -176418897), r = md5_ff(r, i, n, o, t[p + 5], 12, 1200080426), o = md5_ff(o, r, i, n, t[p + 6], 17, -1473231341), n = md5_ff(n, o, r, i, t[p + 7], 22, -45705983), i = md5_ff(i, n, o, r, t[p + 8], 7, 1770035416), r = md5_ff(r, i, n, o, t[p + 9], 12, -1958414417), o = md5_ff(o, r, i, n, t[p + 10], 17, -42063), n = md5_ff(n, o, r, i, t[p + 11], 22, -1990404162), i = md5_ff(i, n, o, r, t[p + 12], 7, 1804603682), r = md5_ff(r, i, n, o, t[p + 13], 12, -40341101), o = md5_ff(o, r, i, n, t[p + 14], 17, -1502002290), n = md5_ff(n, o, r, i, t[p + 15], 22, 1236535329), i = md5_gg(i, n, o, r, t[p + 1], 5, -165796510), r = md5_gg(r, i, n, o, t[p + 6], 9, -1069501632), o = md5_gg(o, r, i, n, t[p + 11], 14, 643717713), n = md5_gg(n, o, r, i, t[p + 0], 20, -373897302), i = md5_gg(i, n, o, r, t[p + 5], 5, -701558691), r = md5_gg(r, i, n, o, t[p + 10], 9, 38016083), o = md5_gg(o, r, i, n, t[p + 15], 14, -660478335), n = md5_gg(n, o, r, i, t[p + 4], 20, -405537848), i = md5_gg(i, n, o, r, t[p + 9], 5, 568446438), r = md5_gg(r, i, n, o, t[p + 14], 9, -1019803690), o = md5_gg(o, r, i, n, t[p + 3], 14, -187363961), n = md5_gg(n, o, r, i, t[p + 8], 20, 1163531501), i = md5_gg(i, n, o, r, t[p + 13], 5, -1444681467), r = md5_gg(r, i, n, o, t[p + 2], 9, -51403784), o = md5_gg(o, r, i, n, t[p + 7], 14, 1735328473), n = md5_gg(n, o, r, i, t[p + 12], 20, -1926607734), i = md5_hh(i, n, o, r, t[p + 5], 4, -378558), r = md5_hh(r, i, n, o, t[p + 8], 11, -2022574463), o = md5_hh(o, r, i, n, t[p + 11], 16, 1839030562), n = md5_hh(n, o, r, i, t[p + 14], 23, -35309556), i = md5_hh(i, n, o, r, t[p + 1], 4, -1530992060), r = md5_hh(r, i, n, o, t[p + 4], 11, 1272893353), o = md5_hh(o, r, i, n, t[p + 7], 16, -155497632), n = md5_hh(n, o, r, i, t[p + 10], 23, -1094730640), i = md5_hh(i, n, o, r, t[p + 13], 4, 681279174), r = md5_hh(r, i, n, o, t[p + 0], 11, -358537222), o = md5_hh(o, r, i, n, t[p + 3], 16, -722521979), n = md5_hh(n, o, r, i, t[p + 6], 23, 76029189), i = md5_hh(i, n, o, r, t[p + 9], 4, -640364487), r = md5_hh(r, i, n, o, t[p + 12], 11, -421815835), o = md5_hh(o, r, i, n, t[p + 15], 16, 530742520), n = md5_hh(n, o, r, i, t[p + 2], 23, -995338651), i = md5_ii(i, n, o, r, t[p + 0], 6, -198630844), r = md5_ii(r, i, n, o, t[p + 7], 10, 1126891415), o = md5_ii(o, r, i, n, t[p + 14], 15, -1416354905), n = md5_ii(n, o, r, i, t[p + 5], 21, -57434055), i = md5_ii(i, n, o, r, t[p + 12], 6, 1700485571), r = md5_ii(r, i, n, o, t[p + 3], 10, -1894986606), o = md5_ii(o, r, i, n, t[p + 10], 15, -1051523), n = md5_ii(n, o, r, i, t[p + 1], 21, -2054922799), i = md5_ii(i, n, o, r, t[p + 8], 6, 1873313359), r = md5_ii(r, i, n, o, t[p + 15], 10, -30611744), o = md5_ii(o, r, i, n, t[p + 6], 15, -1560198380), n = md5_ii(n, o, r, i, t[p + 13], 21, 1309151649), i = md5_ii(i, n, o, r, t[p + 4], 6, -145523070), r = md5_ii(r, i, n, o, t[p + 11], 10, -1120210379), o = md5_ii(o, r, i, n, t[p + 2], 15, 718787259), n = md5_ii(n, o, r, i, t[p + 9], 21, -343485551), i = safe_add(i, a), n = safe_add(n, s), o = safe_add(o, l), r = safe_add(r, c)
			}
			return 16 == mode ? Array(n, o) : Array(i, n, o, r)
		}
		function md5_cmn(t, e, i, n, o, r) {
			return safe_add(bit_rol(safe_add(safe_add(e, t), safe_add(n, r)), o), i)
		}
		function md5_ff(t, e, i, n, o, r, p) {
			return md5_cmn(e & i | ~e & n, t, e, o, r, p)
		}
		function md5_gg(t, e, i, n, o, r, p) {
			return md5_cmn(e & n | i & ~n, t, e, o, r, p)
		}
		function md5_hh(t, e, i, n, o, r, p) {
			return md5_cmn(e ^ i ^ n, t, e, o, r, p)
		}
		function md5_ii(t, e, i, n, o, r, p) {
			return md5_cmn(i ^ (e | ~n), t, e, o, r, p)
		}
		function core_hmac_md5(t, e) {
			var i = str2binl(t);
			i.length > 16 && (i = core_md5(i, t.length * chrsz));
			for (var n = Array(16), o = Array(16), r = 0; 16 > r; r++) n[r] = 909522486 ^ i[r], o[r] = 1549556828 ^ i[r];
			var p = core_md5(n.concat(str2binl(e)), 512 + e.length * chrsz);
			return core_md5(o.concat(p), 640)
		}
		function safe_add(t, e) {
			var i = (65535 & t) + (65535 & e),
				n = (t >> 16) + (e >> 16) + (i >> 16);
			return n << 16 | 65535 & i
		}
		function bit_rol(t, e) {
			return t << e | t >>> 32 - e
		}
		function str2binl(t) {
			for (var e = Array(), i = (1 << chrsz) - 1, n = 0; n < t.length * chrsz; n += chrsz) e[n >> 5] |= (t.charCodeAt(n / chrsz) & i) << n % 32;
			return e
		}
		function binl2str(t) {
			for (var e = "", i = (1 << chrsz) - 1, n = 0; n < 32 * t.length; n += chrsz) e += String.fromCharCode(t[n >> 5] >>> n % 32 & i);
			return e
		}
		function binl2hex(t) {
			for (var e = hexcase ? "0123456789ABCDEF" : "0123456789abcdef", i = "", n = 0; n < 4 * t.length; n++) i += e.charAt(t[n >> 2] >> n % 4 * 8 + 4 & 15) + e.charAt(t[n >> 2] >> n % 4 * 8 & 15);
			return i
		}
		function binl2b64(t) {
			for (var e = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/", i = "", n = 0; n < 4 * t.length; n += 3) for (var o = (t[n >> 2] >> 8 * (n % 4) & 255) << 16 | (t[n + 1 >> 2] >> 8 * ((n + 1) % 4) & 255) << 8 | t[n + 2 >> 2] >> 8 * ((n + 2) % 4) & 255, r = 0; 4 > r; r++) i += 8 * n + 6 * r > 32 * t.length ? b64pad : e.charAt(o >> 6 * (3 - r) & 63);
			return i
		}
		function hexchar2bin(str) {
			for (var arr = [], i = 0; i < str.length; i += 2) arr.push("\\x" + str.substr(i, 2));
			return arr = arr.join(""), eval("var temp = '" + arr + "'"), temp
		}
		function __monitor(t, e) {
			if (!(Math.random() > (e || 1))) try {
				var i = location.protocol + "//ui.ptlogin2.qq.com/cgi-bin/report?id=" + t,
					n = document.createElement("img");
				n.src = i
			} catch (o) {}
		}
		function getEncryption(t, e, i, n) {
			i = i || "", t = t || "";
			for (var o = n ? t : md5(t), r = hexchar2bin(o), p = md5(r + e), a = $.RSA.rsa_encrypt(r), s = (a.length / 2).toString(16), l = pt.TEA.strToBytes(i.toUpperCase(), !0), c = Number(l.length / 2).toString(16); c.length < 4;) c = "0" + c;
			for (; s.length < 4;) s = "0" + s;
			pt.TEA.initkey(p);
			var u = pt.TEA.enAsBase64(s + a + pt.TEA.strToBytes(e) + c + l);
			return pt.TEA.initkey(""), /*setTimeout(function() {
				__monitor(488358, 1)
			}, 0), */u.replace(/[\/\+=]/g, function(t) {
				return {
					"/": "-",
					"+": "*",
					"=": "_"
				}[t]
			})
		}
		function getRSAEncryption(t, e, i) {
			var n = i ? t : md5(t),
				o = n + e.toUpperCase(),
				r = $.RSA.rsa_encrypt(o);
			return r
		}
		var hexcase = 1,
			b64pad = "",
			chrsz = 8,
			mode = 32;
		return {
			getEncryption: getEncryption,
			getRSAEncryption: getRSAEncryption,
			md5: md5,
			hexchar2bin: hexchar2bin
		}
	}();

	pt.plogin = {
		loginUrl: "http://ptlogin2.qq.com/",
		at_account: $.username,
		needShowNewVc: !1,
		pt_verifysession: $.verifysession,
		salt: $.Encryption.hexchar2bin($.salt),
		isRandSalt: 0,
		action: [3, 3],
		js_type: 1,
		low_login_enable: !0,
		low_login_hour: 720,
		
		getSubmitUrl: function(t) {
			var e = pt.plogin.loginUrl + t + "?",
				i = {};
			"login" == t && (i.u = encodeURIComponent(pt.plogin.at_account), i.verifycode = $.verifycode, i.pt_vcode_v1 = pt.plogin.needShowNewVc ? 1 : 0, i.pt_verifysession_v1 = $.verifysession, i.p = $.Encryption.getEncryption($.password, pt.plogin.salt, $.verifycode), i.pt_randsalt = pt.plogin.isRandSalt || 0), i.ptredirect = pt.ptui.target, i.u1 = encodeURIComponent(pt.ptui.s_url), i.h = 1, i.t = 1, i.g = 1, i.from_ui = 1, i.ptlang = pt.ptui.lang, i.action = pt.plogin.action.join("-") + "-" + (new Date - 0), i.js_ver = pt.ptui.ptui_version, i.js_type = pt.plogin.js_type, i.login_sig = pt.ptui.login_sig, i.pt_uistyle = pt.ptui.style, 1 == pt.ptui.low_login && pt.plogin.low_login_enable && (i.low_login_enable = 1, i.low_login_hour = pt.plogin.low_login_hour), "0" != pt.ptui.csimc && (i.csimc = pt.ptui.csimc, i.csnum = pt.ptui.csnum, i.authid = pt.ptui.authid), i.aid = pt.ptui.appid, pt.ptui.daid && (i.daid = pt.ptui.daid), "0" != pt.ptui.pt_3rd_aid && (i.pt_3rd_aid = pt.ptui.pt_3rd_aid), pt.ptui.regmaster && (i.regmaster = pt.ptui.regmaster), pt.ptui.mibao_css && (i.mibao_css = pt.ptui.mibao_css), "1" == pt.ptui.pt_qzone_sig && (i.pt_qzone_sig = 1), "1" == pt.ptui.pt_light && (i.pt_light = 1);
		
			for (var n in i) e += n + "=" + i[n] + "&";
			return e;
		},
		submit: function(t) {
			var e = pt.plogin.getSubmitUrl("login");
			return e;
		}
	};

	console.log(pt.plogin.submit());
}catch(e){
	console.trace(e.stack);
}