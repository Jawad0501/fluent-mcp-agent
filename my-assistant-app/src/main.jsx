import "@assistant-ui/react-ui/styles/index.css";
import "@assistant-ui/react-ui/styles/themes/default.css";
import "./index.scss";

import React from "react";
import ReactDOM from "react-dom/client";
import ChatShell from "./oldChatShell";
import AssistantModal from "./AssistantModal";

const rootEl = document.getElementById("fluent-assistant-chat-root");
// const rootModalEl = document.getElementById("fluent-mcp-agent-root");
if (rootEl) {
  ReactDOM.createRoot(rootEl).render(<ChatShell />);
}


// if (rootModalEl) {
//   ReactDOM.createRoot(rootModalEl).render(<AssistantModal />);
// }