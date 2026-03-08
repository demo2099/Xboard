<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $title }}</title>
  <script>
    window.settings = {
      base_url: "/",
      title: "{{ $title }}",
      version: "{{ $version }}",
      logo: "{{ $logo }}",
      secure_path: "{{ $secure_path }}",
    };
  </script>
  <script type="module" crossorigin src="/assets/admin/assets/index.js"></script>
  <link rel="stylesheet" crossorigin href="/assets/admin/assets/index.css" />
  <link rel="stylesheet" crossorigin href="/assets/admin/assets/vendor.css">
  <script src="/assets/admin/locales/en-US.js"></script>
  <script src="/assets/admin/locales/zh-CN.js"></script>
  <script src="/assets/admin/locales/ko-KR.js"></script>
</head>

<body>
  <div id="root"></div>
  <script>
    (function () {
      const TARGET_HASH = "/config/system/subscribe-template";
      const BTN_ID = "xboard-reset-subscribe-template-btn";
      const PANEL_ID = "xboard-reset-subscribe-template-panel";

      function getAccessToken() {
        try {
          const raw = localStorage.getItem("XBOARD_ACCESS_TOKEN");
          if (!raw) return "";
          const parsed = JSON.parse(raw);
          return parsed && parsed.value ? parsed.value : "";
        } catch (e) {
          return "";
        }
      }

      function shouldShow() {
        return (location.hash || "").indexOf(TARGET_HASH) !== -1;
      }

      function removeUI() {
        const btn = document.getElementById(BTN_ID);
        const panel = document.getElementById(PANEL_ID);
        if (btn) btn.remove();
        if (panel) panel.remove();
      }

      async function resetTemplate(type) {
        const token = getAccessToken();
        if (!token) {
          alert("未检测到登录状态，请重新登录后台后再试。");
          return;
        }

        const confirmed = window.confirm(
          type === "all"
            ? "确定重置全部订阅模板为文件默认模板？"
            : "确定重置当前模板为文件默认模板？"
        );
        if (!confirmed) return;

        const securePath = window.settings && window.settings.secure_path ? window.settings.secure_path : "";
        const url = "/api/v2/" + securePath + "/config/resetSubscribeTemplate";

        try {
          const resp = await fetch(url, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "Authorization": token
            },
            body: JSON.stringify({ type: type })
          });
          const data = await resp.json();
          if (!resp.ok || !data || data.status !== "success") {
            throw new Error(data && data.message ? data.message : "请求失败");
          }
          alert("重置成功，页面将自动刷新。");
          window.location.reload();
        } catch (e) {
          alert("重置失败：" + (e && e.message ? e.message : "未知错误"));
        }
      }

      function ensureUI() {
        if (!shouldShow()) {
          removeUI();
          return;
        }
        if (document.getElementById(BTN_ID)) return;

        const panel = document.createElement("div");
        panel.id = PANEL_ID;
        panel.style.cssText = "position:fixed;right:16px;bottom:16px;z-index:9999;display:flex;gap:8px;align-items:center;background:#111827;color:#fff;padding:8px 10px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.25);font-size:12px;";

        const select = document.createElement("select");
        select.style.cssText = "height:30px;border-radius:8px;border:1px solid #374151;background:#1f2937;color:#fff;padding:0 8px;";
        [
          ["all", "全部模板"],
          ["singbox", "Sing-box"],
          ["clash", "Clash"],
          ["clashmeta", "ClashMeta"],
          ["stash", "Stash"],
          ["surge", "Surge"],
          ["surfboard", "Surfboard"]
        ].forEach(function (item) {
          const op = document.createElement("option");
          op.value = item[0];
          op.textContent = item[1];
          select.appendChild(op);
        });

        const btn = document.createElement("button");
        btn.id = BTN_ID;
        btn.textContent = "重置为文件默认";
        btn.style.cssText = "height:30px;border:none;border-radius:8px;padding:0 10px;background:#2563eb;color:#fff;cursor:pointer;";
        btn.onclick = function () {
          resetTemplate(select.value);
        };

        panel.appendChild(select);
        panel.appendChild(btn);
        document.body.appendChild(panel);
      }

      window.addEventListener("hashchange", ensureUI);
      window.addEventListener("load", ensureUI);
      setInterval(ensureUI, 1200);
    })();
  </script>
</body>

</html>
