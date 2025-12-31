(function () {
  let lastText = "";
  let popup;

  function getSelectedText() {
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) return "";

    const text = selection.toString().trim();
    if (!text || text.length < 3) return "";

    const range = selection.getRangeAt(0);
    const container = range.commonAncestorContainer;

    // Ignore inputs, textareas, editable areas
    if (
      container.nodeType === 1 &&
      (container.closest("input, textarea, [contenteditable='true'], pre, code"))
    ) {
      return "";
    }

    return text;
  }

  function showAskFluent(text, x, y) {
    if (!popup) {
      popup = document.createElement("button");
      popup.textContent = "Ask Fluent";
      popup.style.position = "fixed";
      popup.style.zIndex = "999999";
      popup.style.padding = "6px 10px";
      popup.style.borderRadius = "6px";
      popup.style.border = "1px solid #ccc";
      popup.style.background = "#111";
      popup.style.color = "#fff";
      popup.style.cursor = "pointer";
      popup.style.fontSize = "13px";

      popup.onclick = () => {
        window.dispatchEvent(
          new CustomEvent("fluent:ask", {
            detail: { text: lastText }
          })
        );
        popup.remove();
        popup = null;
        window.getSelection()?.removeAllRanges();
      };

      document.body.appendChild(popup);
    }

    popup.style.left = x + "px";
    popup.style.top = y + "px";
  }

  function hideAskFluent() {
    if (popup) {
      popup.remove();
      popup = null;
    }
  }

  function handleSelection() {
    // Delay allows browser to finalize selection
    setTimeout(() => {
      const text = getSelectedText();
      if (!text || text === lastText) return;

      lastText = text;

      const selection = window.getSelection();
      const range = selection.getRangeAt(0);
      const rect = range.getBoundingClientRect();

      showAskFluent(text, rect.right + 8, rect.top - 6);
    }, 0);
  }

  document.addEventListener("mouseup", handleSelection);
  document.addEventListener("keyup", handleSelection);
  document.addEventListener("mousedown", hideAskFluent);
})();
