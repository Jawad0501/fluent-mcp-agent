import React from "react";
import { makeAssistantToolUI} from "@assistant-ui/react";

export const ConfirmToolExecution = makeAssistantToolUI({
  toolName: "ConfirmToolExecution",
  render: ({ args, result }) => {
    return (
      <div className="search-results">
        <h3>Search: {args.query}</h3>
        {result.results.map((item) => (
          <div key={item.id}>
            <a href={item.url}>{item.title}</a>
            <p>{item.snippet}</p>
          </div>
        ))}
      </div>
    );
  },
  // render: ({ args, status, resume }) => {
  //   // The tool arguments passed from the model
  //   const { toolName, toolArgs } = args;

  //   // Only show confirmation when fresh
  //   if (status.type === "incomplete" && !status.running) {
  //     return (
  //       <div className="p-4 border bg-gray-100 rounded-md space-y-2">
  //         <p className="font-semibold">
  //           The assistant wants to run the tool: <strong>{toolName}</strong>
  //         </p>
  //         {toolArgs && (
  //           <pre className="text-sm bg-white p-2 rounded">{JSON.stringify(toolArgs, null, 2)}</pre>
  //         )}
  //         <div className="flex gap-2">
  //           <button
  //             className="px-3 py-1 bg-green-600 text-white rounded"
  //             onClick={() => resume({ confirmed: true })}
  //           >
  //             Yes
  //           </button>
  //           <button
  //             className="px-3 py-1 bg-red-500 text-white rounded"
  //             onClick={() => resume({ confirmed: false })}
  //           >
  //             No
  //           </button>
  //         </div>
  //       </div>
  //     );
  //   }

  //   // This UI only handles the confirmation step
  //   return null;
  // },
});
