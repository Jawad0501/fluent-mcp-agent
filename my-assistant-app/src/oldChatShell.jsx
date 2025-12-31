import React from 'react';
import { AssistantRuntimeProvider } from '@assistant-ui/react';
import { useChatRuntime } from '@assistant-ui/react-ai-sdk';
import { Thread } from '@/components/assistant-ui/thread';
import { ThreadList } from '@/components/assistant-ui/thread-list';
import { SidebarProvider, SidebarInset, SidebarTrigger } from '@/components/ui/sidebar';
import { ConfirmToolExecution } from "./ConfirmToolExecution";
import { WaitUntilToolExecuted } from "./WaitUntilToolExecuted";
import { 
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Settings } from 'lucide-react';
import { Button } from '@/components/ui/button';

export default function ChatShell() {
  const [selectedProvider, setSelectedProvider] = React.useState(
    typeof fluentMcpAgent !== "undefined" && fluentMcpAgent.defaultProvider
      ? fluentMcpAgent.defaultProvider
      : "ollama"
  );
  
  const getInitialModel = () => {
    if (
      typeof fluentMcpAgent !== "undefined" &&
      fluentMcpAgent.enabledProviders &&
      fluentMcpAgent.enabledProviders.length > 0
    ) {
      const provider =
        fluentMcpAgent.defaultProvider || fluentMcpAgent.enabledProviders[0];
      const available = fluentMcpAgent.availableModels || [];
      const found = available.find((obj) => obj[provider]);
      if (found && Array.isArray(found[provider]) && found[provider].length > 0) {
        return found[provider][0];
      }
    }
    return "gpt-4";
  };
  
  const [selectedModel, setSelectedModel] = React.useState(getInitialModel());
  
//   // Custom fetch function for streaming
//   const streamingFetch = React.useCallback(async (url, options) => {
//     console.log('Streaming fetch to:', url);
    
//     const response = await fetch(url, {
//       ...options,
//       headers: {
//         ...options.headers,
//         'Accept': 'text/event-stream',
//       },
//     });

//     if (!response.ok) {
//       throw new Error(`HTTP error! status: ${response.status}`);
//     }

//     // Return the response as-is for streaming
//     return response;
//   }, []);
  
  // Use useChatRuntime for streaming support
  const runtime = useChatRuntime();

  return (
    <AssistantRuntimeProvider runtime={runtime}>
      <SidebarProvider>
        <div className="flex h-screen w-full">
          {/* Sidebar with ThreadList */}
          <ThreadList />
          
          {/* Main Chat Area */}
          <SidebarInset className="flex flex-col flex-1">
            {/* Header */}
            <header className="flex h-14 items-center gap-2 border-b px-4">
              <SidebarTrigger className="-ml-1" />
              <div className="h-4 w-px bg-border mx-2" />

              {/* Provider Selector */}
              <Select
                value={selectedProvider}
                onValueChange={(provider) => {
                  setSelectedProvider(provider);
                  // Find first model for the newly selected provider
                  if (
                    typeof fluentMcpAgent !== "undefined" &&
                    Array.isArray(fluentMcpAgent.availableModels)
                  ) {
                    const found = fluentMcpAgent.availableModels.find(
                      (obj) => obj[provider]
                    );
                    if (
                      found &&
                      Array.isArray(found[provider]) &&
                      found[provider].length > 0
                    ) {
                      setSelectedModel(found[provider][0]);
                    } else {
                      setSelectedModel("");
                    }
                  }
                }}
              >
                <SelectTrigger className="w-[180px]">
                  <SelectValue placeholder="Select provider" />
                </SelectTrigger>
                <SelectContent>
                  {typeof fluentMcpAgent !== "undefined" &&
                    Array.isArray(fluentMcpAgent.enabledProviders) &&
                    fluentMcpAgent.enabledProviders.map((provider) => {
                      const providerLabels = {
                        ollama: "🦙 Ollama",
                        openai: "🤖 OpenAI",
                        anthropic: "🧠 Claude",
                      };
                      return (
                        <SelectItem key={provider} value={provider}>
                          {providerLabels[provider] || provider}
                        </SelectItem>
                      );
                    })}
                </SelectContent>
              </Select>
              
              {/* Model Selector */}
              <Select value={selectedModel} onValueChange={setSelectedModel}>
                <SelectTrigger className="w-[180px]">
                  <SelectValue placeholder="Select model" />
                </SelectTrigger>
                <SelectContent>
                  {typeof fluentMcpAgent !== "undefined" &&
                    Array.isArray(fluentMcpAgent.availableModels) &&
                    fluentMcpAgent.availableModels.map((providerModelsObj) => {
                      const provider = Object.keys(providerModelsObj)[0];
                      const models = providerModelsObj[provider];
                      if (provider !== selectedProvider) return null;
                      return models.map((model) => (
                        <SelectItem key={model} value={model}>
                          {model}
                        </SelectItem>
                      ));
                    })}
                </SelectContent>
              </Select>

              <div className="flex-1" />
              
              <Button variant="ghost" size="icon">
                <Settings className="h-4 w-4" />
              </Button>
            </header>

            {/* Thread/Chat Area */}
            <div className="flex-1 overflow-hidden">
                <ConfirmToolExecution />
                <WaitUntilToolExecuted />
                <Thread />
            </div>
          </SidebarInset>
        </div>
      </SidebarProvider>
    </AssistantRuntimeProvider>
  );
}