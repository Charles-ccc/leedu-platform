let url = import.meta.env.VITE_APP_URL || "";

declare const window: any;

if (typeof window.leedu_app_url !== "undefined" && window.leedu_app_url) {
  url = window.leedu_app_url;
}

export default {
  url: url,
};
