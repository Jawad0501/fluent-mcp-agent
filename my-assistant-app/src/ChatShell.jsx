import React from "react";
import { AssistantRuntimeProvider } from "@assistant-ui/react";
import { useDataStreamRuntime } from "@assistant-ui/react-data-stream";
import { Thread } from "@/components/assistant-ui/thread";
import { ThreadList } from "@/components/assistant-ui/thread-list";
import { Settings, Menu, X, ChevronDown, Sparkles, MessageSquareText, Plus, Database, Brain, Layers } from "lucide-react";
import { TooltipProvider } from "@/components/ui/tooltip";
import { ConfirmToolExecution } from "./ConfirmToolExecution";
import { WaitUntilToolExecuted } from "./WaitUntilToolExecuted";

export default function ChatShell() {
  const [sidebarOpen, setSidebarOpen] = React.useState(true);
  const [settingsSidebarOpen, setSettingsSidebarOpen] = React.useState(false);
  
  // Get initial provider from WordPress
  const [selectedProvider, setSelectedProvider] = React.useState(() => {
    if (typeof fluentMcpAgent !== "undefined" && fluentMcpAgent.defaultProvider) {
      return fluentMcpAgent.defaultProvider;
    }
    if (fluentMcpAgent?.enabledProviders?.length) {
      return fluentMcpAgent.enabledProviders[0];
    }
    return "ollama";
  });

  // Get initial model based on provider
  const getInitialModel = () => {
    if (typeof fluentMcpAgent !== "undefined" && fluentMcpAgent.availableModels?.length) {
      const found = fluentMcpAgent.availableModels.find(
        (obj) => obj[selectedProvider]
      );
      if (found?.[selectedProvider]?.length) {
        return found[selectedProvider][0];
      }
    }
    return "gpt-4";
  };

  const [selectedModel, setSelectedModel] = React.useState(getInitialModel);
  const [providerDropdownOpen, setProviderDropdownOpen] = React.useState(false);
  const [modelDropdownOpen, setModelDropdownOpen] = React.useState(false);

  // RAG Settings State
  const [ragSettings, setRagSettings] = React.useState({
    enabled: false,
    chunkSize: 1000,
    chunkOverlap: 200,
    topK: 5,
    similarityThreshold: 0.7,
    embeddingModel: 'text-embedding-ada-002',
    vectorStore: 'pinecone'
  });

  // Update model when provider changes
  React.useEffect(() => {
    if (fluentMcpAgent?.availableModels?.length) {
      const found = fluentMcpAgent.availableModels.find(
        (obj) => obj[selectedProvider]
      );
      if (found?.[selectedProvider]?.length) {
        const newModel = found[selectedProvider][0];
        setSelectedModel(newModel);
      }
    }
  }, [selectedProvider]);

  // Create runtime for assistant-ui with updated body
  const runtime = useDataStreamRuntime({
    api: "/api/chat",
    body: {
      provider: selectedProvider,
      model: selectedModel,
      ragSettings: ragSettings.enabled ? ragSettings : undefined
    },
    tools: {
        WaitUntilToolExecuted: {
            description: "Shows tool execution progress"
        },
        ConfirmToolExecution: {
            description: "Confirms tool execution"
        }
    },
    onError(error) {
      console.error("Assistant stream error:", error);
    },
  });

  React.useEffect(() => {
    function handleAskFluent(event) {
      const text = event?.detail?.text;
      if (!text) return;

      console.log(runtime.thread);
  
      // Make runtime start a *new chat turn* including selected text
      runtime.thread.append({
        
        role: "user",
        content: [{ type: "text", text: `Help me with this:\n\n"${text}"` }]
          
      });
    }
  
    window.addEventListener("fluent:ask", handleAskFluent);
    return () => window.removeEventListener("fluent:ask", handleAskFluent);
  }, [runtime]);
  

  const handleProviderChange = (provider) => {
    console.log('here')
    setSelectedProvider(provider);
    const found = fluentMcpAgent?.availableModels?.find(
      (obj) => obj[provider]
    );
    if (found?.[provider]?.length) {
      setSelectedModel(found[provider][0]);
    }
    setProviderDropdownOpen(false);
  };

  const handleModelChange = (model) => {
    setSelectedModel(model);
    setModelDropdownOpen(false);
  };

  const currentModels = fluentMcpAgent?.availableModels?.find(
    (obj) => Object.keys(obj)[0] === selectedProvider
  )?.[selectedProvider] || [];

  const handleRagSettingChange = (key, value) => {
    setRagSettings(prev => ({
      ...prev,
      [key]: value
    }));
  };

  return (
    <TooltipProvider>
      <AssistantRuntimeProvider runtime={runtime}>
        <div style={{
            display: 'flex',
            height: 'calc(100vh - 32px)',
            backgroundColor: '#ffffff',
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            overflow: 'hidden',
            position: 'relative'
        }}>
        {/* Left Sidebar */}
        <div
        style={{
            width: sidebarOpen ? "272px" : "0",
            minWidth: sidebarOpen ? "272px" : "0",
            backgroundColor: "#FFFFFF",
            transition: "all 0.3s ease",
            overflow: "hidden",
            display: "flex",
            flexDirection: "column",
            borderRight: "1px solid #ececec",
        }}
        >
        <div
            style={{
            width: "272px",
            height: "100%",
            display: "flex",
            flexDirection: "column",
            }}
        >
            {/* Sticky Header */}
            <div
            style={{
                position: "sticky",
                top: 0,
                zIndex: 10,
                backgroundColor: "#FFFFFF",
                padding: "0 20px",
                borderBottom: "1px solid #f0f0f0",
                height: "64px",
                display: "flex",
                alignItems: "center"
            }}
            >
              <div
                    style={{
                    display: "flex",
                    alignItems: "center",
                    gap: "8px",
                    fontWeight: 600,
                    fontSize: "14px",
                    letterSpacing: "0.04em",
                    color: "#111827",
                    textTransform: "uppercase",
                    }}
                >
                    <Sparkles size={16} />
                    FLUENT AGENT
                </div>
            </div>

            {/* Content */}
            <div
            style={{
                flex: 1,
                overflowY: "auto",
                padding: "16px",
                display: "flex",
                flexDirection: "column",
            }}
            >

                <div
                    style={{
                        padding: "16px",
                        marginBottom: "16px",
                        backgroundColor: "#f9fafb",
                        borderRadius: "8px",
                        border: "1px solid #e5e7eb",
                        display: "flex",
                        flexDirection: "column",
                        gap: "12px",
                        color: "#6B7280",
                        fontSize: "13px",
                        lineHeight: "1.6",
                    }}
                >
                    <div style={{ display: "flex", alignItems: "center", gap: "8px" }}>
                    <MessageSquareText size={18} style={{ color: "#9CA3AF", flexShrink: 0 }} />
                    <div style={{ fontWeight: 600, color: "#111827" }}>
                        Welcome to Fluent Agent
                    </div>
                    </div>

                    <div>
                    Your AI assistant can help with content, automation, and WordPress actions.
                    </div>

                    <div>
                    Start typing to begin a new conversation.
                    </div>
                </div>
              

              {/* ThreadList - Always show */}
              <div style={{ flex: 1 }}>
                <ThreadList />
              </div>
            </div>
        </div>
        </div>

        {/* Main Content Area */}
        <div style={{
          flex: 1,
          display: 'flex',
          flexDirection: 'column',
          backgroundColor: '#ffffff',
          overflow: 'hidden'
        }}>
          {/* Header */}
          <header style={{
            height: '64px',
            borderBottom: '1px solid #ececec',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            padding: '0 24px',
            backgroundColor: '#ffffff',
            position: 'relative',
            zIndex: 1001
          }}>

            <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
              {/* Sidebar Toggle */}
              <button
                onClick={() => setSidebarOpen(!sidebarOpen)}
                style={{
                  width: '32px',
                  height: '32px',
                  border: 'none',
                  borderRadius: '6px',
                  backgroundColor: 'transparent',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  transition: 'background-color 0.2s',
                  color: '#565656',
                  padding: 0
                }}
                onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#f7f7f7'}
                onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'transparent'}
              >
                {sidebarOpen ? <X size={20} /> : <Menu size={20} />}
              </button>

                {/* Provider Selector */}
                {fluentMcpAgent?.enabledProviders?.length > 1 && (
                <div style={{ position: 'relative' }}>
                  <button
                    onClick={() => setProviderDropdownOpen(!providerDropdownOpen)}
                    style={{
                      padding: '8px 16px',
                      border: '1px solid #d0d0d0',
                      borderRadius: '8px',
                      backgroundColor: '#ffffff',
                      cursor: 'pointer',
                      fontSize: '14px',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '8px',
                      color: '#2d2d2d',
                      fontWeight: '500',
                      transition: 'all 0.2s',
                      minWidth: '120px'
                    }}
                    onMouseEnter={(e) => {
                      e.currentTarget.style.backgroundColor = '#f7f7f7';
                      e.currentTarget.style.borderColor = '#b0b0b0';
                    }}
                    onMouseLeave={(e) => {
                      e.currentTarget.style.backgroundColor = '#ffffff';
                      e.currentTarget.style.borderColor = '#d0d0d0';
                    }}
                  >
                    <span style={{ flex: 1, textAlign: 'left' }}>
                      {selectedProvider.charAt(0).toUpperCase() + selectedProvider.slice(1)}
                    </span>
                    <ChevronDown size={16} style={{ flexShrink: 0 }} />
                  </button>
                  {providerDropdownOpen && (
                    <div style={{
                      position: 'absolute',
                      top: 'calc(100% + 4px)',
                      left: 0,
                      minWidth: '160px',
                      backgroundColor: '#ffffff',
                      border: '1px solid #d0d0d0',
                      borderRadius: '8px',
                      boxShadow: '0 4px 12px rgba(0,0,0,0.1)',
                      zIndex: 1000,
                      overflow: 'hidden'
                    }}>
                      {fluentMcpAgent?.enabledProviders?.map((provider) => (
                        <div
                          key={provider}
                          onClick={() => handleProviderChange(provider)}
                          style={{
                            padding: '12px 16px',
                            cursor: 'pointer',
                            fontSize: '14px',
                            color: '#2d2d2d',
                            backgroundColor: selectedProvider === provider ? '#f0f0f0' : '#ffffff',
                            transition: 'background-color 0.2s'
                          }}
                          onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#f7f7f7'}
                          onMouseLeave={(e) => {
                            if (selectedProvider !== provider) {
                              e.currentTarget.style.backgroundColor = '#ffffff';
                            }
                          }}
                        >
                          {provider.charAt(0).toUpperCase() + provider.slice(1)}
                        </div>
                      ))}
                    </div>
                  )}
                </div>
                )}

                {/* Model Selector */}
                {currentModels.length > 0 && (
                    <div style={{ position: 'relative' }}>
                    <button
                        onClick={() => setModelDropdownOpen(!modelDropdownOpen)}
                        style={{
                        padding: '8px 16px',
                        border: '1px solid #d0d0d0',
                        borderRadius: '8px',
                        backgroundColor: '#ffffff',
                        cursor: 'pointer',
                        fontSize: '14px',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '8px',
                        color: '#2d2d2d',
                        fontWeight: '500',
                        transition: 'all 0.2s',
                        minWidth: '140px'
                        }}
                        onMouseEnter={(e) => {
                        e.currentTarget.style.backgroundColor = '#f7f7f7';
                        e.currentTarget.style.borderColor = '#b0b0b0';
                        }}
                        onMouseLeave={(e) => {
                        e.currentTarget.style.backgroundColor = '#ffffff';
                        e.currentTarget.style.borderColor = '#d0d0d0';
                        }}
                    >
                        <span style={{ flex: 1, textAlign: 'left', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                        {selectedModel}
                        </span>
                        <ChevronDown size={16} style={{ flexShrink: 0 }} />
                    </button>
                    {modelDropdownOpen && (
                        <div style={{
                        position: 'absolute',
                        top: 'calc(100% + 4px)',
                        left: 0,
                        minWidth: '200px',
                        backgroundColor: '#ffffff',
                        border: '1px solid #d0d0d0',
                        borderRadius: '8px',
                        boxShadow: '0 4px 12px rgba(0,0,0,0.1)',
                        zIndex: 1000,
                        overflow: 'hidden',
                        maxHeight: '300px',
                        overflowY: 'auto'
                        }}>
                        {currentModels.map((model) => (
                            <div
                            key={model}
                            onClick={() => handleModelChange(model)}
                            style={{
                                padding: '12px 16px',
                                cursor: 'pointer',
                                fontSize: '14px',
                                color: '#2d2d2d',
                                backgroundColor: selectedModel === model ? '#f0f0f0' : '#ffffff',
                                transition: 'background-color 0.2s'
                            }}
                            onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#f7f7f7'}
                            onMouseLeave={(e) => {
                                if (selectedModel !== model) {
                                e.currentTarget.style.backgroundColor = '#ffffff';
                                }
                            }}
                            >
                            {model}
                            </div>
                        ))}
                        </div>
                    )}
                    </div>
                )}

             
            </div>

            {/* Settings Button */}
            <button
              onClick={() => setSettingsSidebarOpen(!settingsSidebarOpen)}
              style={{
                width: '36px',
                height: '36px',
                border: 'none',
                borderRadius: '6px',
                backgroundColor: settingsSidebarOpen ? '#f7f7f7' : 'transparent',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                transition: 'background-color 0.2s',
                color: '#565656',
                padding: 0
              }}
              onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#f7f7f7'}
              onMouseLeave={(e) => {
                if (!settingsSidebarOpen) {
                  e.currentTarget.style.backgroundColor = 'transparent';
                }
              }}
              title="Settings"
            >
              <Settings size={20} />
            </button>
          </header>

          {/* Chat Area */}
          <div style={{
            flex: 1,
            overflowY: 'auto',
            backgroundColor: '#ffffff',
            position: 'relative'
          }}>
            <Thread welcomeMessage={null} />
          </div>
        </div>

        {/* Right Settings Sidebar */}
        <div
          style={{
            width: settingsSidebarOpen ? "320px" : "0",
            minWidth: settingsSidebarOpen ? "320px" : "0",
            backgroundColor: "#FFFFFF",
            transition: "all 0.3s ease",
            overflow: "hidden",
            display: "flex",
            flexDirection: "column",
            borderLeft: "1px solid #ececec",
          }}
        >
          <div
            style={{
              width: "320px",
              height: "100%",
              display: "flex",
              flexDirection: "column",
            }}
          >
            {/* Settings Header */}
            <div
              style={{
                position: "sticky",
                top: 0,
                zIndex: 10,
                backgroundColor: "#FFFFFF",
                padding: "0 20px",
                borderBottom: "1px solid #f0f0f0",
                height: "64px",
                display: "flex",
                alignItems: "center",
                justifyContent: "space-between"
              }}
            >
              <div
                style={{
                  display: "flex",
                  alignItems: "center",
                  gap: "8px",
                  fontWeight: 600,
                  fontSize: "16px",
                  color: "#111827",
                }}
              >
                Settings
              </div>
              <button
                onClick={() => setSettingsSidebarOpen(false)}
                style={{
                  width: '28px',
                  height: '28px',
                  border: 'none',
                  borderRadius: '6px',
                  backgroundColor: 'transparent',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  transition: 'background-color 0.2s',
                  color: '#565656',
                  padding: 0
                }}
                onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#f7f7f7'}
                onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'transparent'}
              >
                <X size={18} />
              </button>
            </div>

            {/* Settings Content */}
            <div
              style={{
                flex: 1,
                overflowY: "auto",
                padding: "20px",
              }}
            >
              {/* RAG Settings Section */}
              <div style={{ marginBottom: '24px' }}>
                <div style={{
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  marginBottom: '16px',
                  paddingBottom: '12px',
                  borderBottom: '1px solid #e5e7eb'
                }}>
                  <div style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px',
                    fontWeight: 600,
                    fontSize: '14px',
                    color: '#111827',
                  }}>
                    <Database size={16} />
                    RAG Configuration
                  </div>
                  <label style={{ position: 'relative', display: 'inline-block', width: '44px', height: '24px', cursor: 'pointer' }}>
                    <input
                      type="checkbox"
                      checked={ragSettings.enabled}
                      onChange={(e) => handleRagSettingChange('enabled', e.target.checked)}
                      style={{ opacity: 0, width: 0, height: 0 }}
                    />
                    <span style={{
                      position: 'absolute',
                      cursor: 'pointer',
                      top: 0,
                      left: 0,
                      right: 0,
                      bottom: 0,
                      backgroundColor: ragSettings.enabled ? '#2271b1' : '#8c8f94',
                      transition: '.3s',
                      borderRadius: '24px'
                    }}></span>
                    <span style={{
                      position: 'absolute',
                      content: '',
                      height: '18px',
                      width: '18px',
                      left: ragSettings.enabled ? '23px' : '3px',
                      bottom: '3px',
                      backgroundColor: 'white',
                      transition: '.3s',
                      borderRadius: '50%'
                    }}></span>
                  </label>
                </div>

                <div style={{ opacity: ragSettings.enabled ? 1 : 0.5, pointerEvents: ragSettings.enabled ? 'auto' : 'none' }}>
                  {/* Chunk Size */}
                  <div style={{ marginBottom: '16px' }}>
                    <label style={{
                      display: 'block',
                      fontSize: '13px',
                      fontWeight: 500,
                      color: '#374151',
                      marginBottom: '6px'
                    }}>
                      Chunk Size
                    </label>
                    <input
                      type="number"
                      value={ragSettings.chunkSize}
                      onChange={(e) => handleRagSettingChange('chunkSize', parseInt(e.target.value))}
                      style={{
                        width: '100%',
                        padding: '8px 12px',
                        border: '1px solid #d1d5db',
                        borderRadius: '6px',
                        fontSize: '14px',
                        outline: 'none'
                      }}
                      onFocus={(e) => e.target.style.borderColor = '#2271b1'}
                      onBlur={(e) => e.target.style.borderColor = '#d1d5db'}
                    />
                    <p style={{ margin: '4px 0 0', fontSize: '12px', color: '#6b7280' }}>
                      Number of tokens per chunk
                    </p>
                  </div>

                  {/* Chunk Overlap */}
                  <div style={{ marginBottom: '16px' }}>
                    <label style={{
                      display: 'block',
                      fontSize: '13px',
                      fontWeight: 500,
                      color: '#374151',
                      marginBottom: '6px'
                    }}>
                      Chunk Overlap
                    </label>
                    <input
                      type="number"
                      value={ragSettings.chunkOverlap}
                      onChange={(e) => handleRagSettingChange('chunkOverlap', parseInt(e.target.value))}
                      style={{
                        width: '100%',
                        padding: '8px 12px',
                        border: '1px solid #d1d5db',
                        borderRadius: '6px',
                        fontSize: '14px',
                        outline: 'none'
                      }}
                      onFocus={(e) => e.target.style.borderColor = '#2271b1'}
                      onBlur={(e) => e.target.style.borderColor = '#d1d5db'}
                    />
                    <p style={{ margin: '4px 0 0', fontSize: '12px', color: '#6b7280' }}>
                      Overlapping tokens between chunks
                    </p>
                  </div>

                  {/* Top K */}
                  <div style={{ marginBottom: '16px' }}>
                    <label style={{
                      display: 'block',
                      fontSize: '13px',
                      fontWeight: 500,
                      color: '#374151',
                      marginBottom: '6px'
                    }}>
                      Top K Results
                    </label>
                    <input
                      type="number"
                      value={ragSettings.topK}
                      onChange={(e) => handleRagSettingChange('topK', parseInt(e.target.value))}
                      style={{
                        width: '100%',
                        padding: '8px 12px',
                        border: '1px solid #d1d5db',
                        borderRadius: '6px',
                        fontSize: '14px',
                        outline: 'none'
                      }}
                      onFocus={(e) => e.target.style.borderColor = '#2271b1'}
                      onBlur={(e) => e.target.style.borderColor = '#d1d5db'}
                    />
                    <p style={{ margin: '4px 0 0', fontSize: '12px', color: '#6b7280' }}>
                      Number of similar chunks to retrieve
                    </p>
                  </div>

                  {/* Similarity Threshold */}
                  <div style={{ marginBottom: '16px' }}>
                    <label style={{
                      display: 'block',
                      fontSize: '13px',
                      fontWeight: 500,
                      color: '#374151',
                      marginBottom: '6px'
                    }}>
                      Similarity Threshold
                    </label>
                    <input
                      type="number"
                      step="0.1"
                      min="0"
                      max="1"
                      value={ragSettings.similarityThreshold}
                      onChange={(e) => handleRagSettingChange('similarityThreshold', parseFloat(e.target.value))}
                      style={{
                        width: '100%',
                        padding: '8px 12px',
                        border: '1px solid #d1d5db',
                        borderRadius: '6px',
                        fontSize: '14px',
                        outline: 'none'
                      }}
                      onFocus={(e) => e.target.style.borderColor = '#2271b1'}
                      onBlur={(e) => e.target.style.borderColor = '#d1d5db'}
                    />
                    <p style={{ margin: '4px 0 0', fontSize: '12px', color: '#6b7280' }}>
                      Minimum similarity score (0-1)
                    </p>
                  </div>

                  {/* Embedding Model */}
                  <div style={{ marginBottom: '16px' }}>
                    <label style={{
                      display: 'block',
                      fontSize: '13px',
                      fontWeight: 500,
                      color: '#374151',
                      marginBottom: '6px'
                    }}>
                      Embedding Model
                    </label>
                    <select
                      value={ragSettings.embeddingModel}
                      onChange={(e) => handleRagSettingChange('embeddingModel', e.target.value)}
                      style={{
                        width: '100%',
                        padding: '8px 12px',
                        border: '1px solid #d1d5db',
                        borderRadius: '6px',
                        fontSize: '14px',
                        outline: 'none',
                        backgroundColor: '#ffffff',
                        cursor: 'pointer'
                      }}
                      onFocus={(e) => e.target.style.borderColor = '#2271b1'}
                      onBlur={(e) => e.target.style.borderColor = '#d1d5db'}
                    >
                      <option value="text-embedding-ada-002">text-embedding-ada-002</option>
                      <option value="text-embedding-3-small">text-embedding-3-small</option>
                      <option value="text-embedding-3-large">text-embedding-3-large</option>
                    </select>
                  </div>

                  {/* Vector Store */}
                  <div style={{ marginBottom: '16px' }}>
                    <label style={{
                      display: 'block',
                      fontSize: '13px',
                      fontWeight: 500,
                      color: '#374151',
                      marginBottom: '6px'
                    }}>
                      Vector Store
                    </label>
                    <select
                      value={ragSettings.vectorStore}
                      onChange={(e) => handleRagSettingChange('vectorStore', e.target.value)}
                      style={{
                        width: '100%',
                        padding: '8px 12px',
                        border: '1px solid #d1d5db',
                        borderRadius: '6px',
                        fontSize: '14px',
                        outline: 'none',
                        backgroundColor: '#ffffff',
                        cursor: 'pointer'
                      }}
                      onFocus={(e) => e.target.style.borderColor = '#2271b1'}
                      onBlur={(e) => e.target.style.borderColor = '#d1d5db'}
                    >
                      <option value="pinecone">Pinecone</option>
                      <option value="weaviate">Weaviate</option>
                      <option value="qdrant">Qdrant</option>
                      <option value="chroma">ChromaDB</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Click outside to close dropdowns */}
        {(modelDropdownOpen || providerDropdownOpen) && (
          <div
            onClick={() => {
              setModelDropdownOpen(false);
              setProviderDropdownOpen(false);
            }}
            style={{
              position: 'fixed',
              top: 0,
              left: 0,
              right: 0,
              bottom: 0,
              zIndex: 999
            }}
          />
        )}
      </div>
    </AssistantRuntimeProvider>
    </TooltipProvider>
  );
}