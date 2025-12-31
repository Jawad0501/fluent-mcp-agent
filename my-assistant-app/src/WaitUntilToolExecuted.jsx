import React from "react";
import { makeAssistantToolUI } from "@assistant-ui/react";
import { Spinner } from "@/components/ui/spinner"; // any loading indicator

export const WaitUntilToolExecuted = makeAssistantToolUI({
  toolName: "WaitUntilToolExecuted",
  render: ({ args, status, result }) => {
    // While the tool is running
    if (status.type === "running") {
      return (
        <div className="flex items-center gap-2 p-3 bg-yellow-100 rounded-md">
          <Spinner className="h-5 w-5 animate-spin" />
          <span>Executing {args.toolName}…</span>
        </div>
      );
    }

    // Tool completed
    if (status.type === "completed" && result) {
      return (
        <div className="p-3 border bg-white rounded-md">
          <h4 className="font-semibold">Tool Result:</h4>
          <pre className="text-sm">{JSON.stringify(result, null, 2)}</pre>
        </div>
      );
    }

    // Default: nothing to show
    return null;
  },
});
