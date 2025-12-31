import React from "react";
import { AssistantRuntimeProvider } from "@assistant-ui/react";
import { useDataStreamRuntime } from "@assistant-ui/react-data-stream";
import { Thread } from "@/components/assistant-ui/thread";
import { ThreadList } from "@/components/assistant-ui/thread-list";
import { Settings, Menu, X, ChevronDown, Sparkles, MessageSquareText, Plus, Database, Brain, Layers, Search, Moon, Sun } from "lucide-react";
import { TooltipProvider } from "@/components/ui/tooltip";
import { ConfirmToolExecution } from "./ConfirmToolExecution";
import { WaitUntilToolExecuted } from "./WaitUntilToolExecuted";

export default function ChatShell() {
  // Theme colors configuration
  const theme = {
    light: {
      bg: '#ffffff',
      bgSecondary: '#f9fafb',
      bgTertiary: '#f3f4f6',
      border: '#ececec',
      borderSecondary: '#e5e7eb',
      borderTertiary: '#d1d5db',
      text: '#111827',
      textSecondary: '#374151',
      textTertiary: '#6b7280',
      textMuted: '#9ca3af',
      hover: '#f7f7f7',
      primary: '#2271b1',
      primaryHover: '#135e96',
    },
    dark: {
      bg: '#1f2937',
      bgSecondary: '#111827',
      bgTertiary: '#374151',
      border: '#374151',
      borderSecondary: '#4b5563',
      borderTertiary: '#6b7280',
      text: '#f9fafb',
      textSecondary: '#e5e7eb',
      textTertiary: '#d1d5db',
      textMuted: '#9ca3af',
      hover: '#374151',
      primary: '#5b8fd8',
      primaryHover: '#4a7bc8',
    }
  };

  // Dark Mode State with localStorage persistence
  const [darkMode, setDarkMode] = React.useState(() => {
    if (typeof window !== 'undefined') {
      const saved = localStorage.getItem('fluentAgentDarkMode');
      return saved ? JSON.parse(saved) : false;
    }
    return false;
  });

  const colors = darkMode ? theme.dark : theme.light;

  // Set CSS variables for Thread component and save dark mode preference
  React.useEffect(() => {
    if (typeof window !== 'undefined') {
      localStorage.setItem('fluentAgentDarkMode', JSON.stringify(darkMode));
      // Update CSS variables for Thread component
      const root = document.documentElement;
      root.style.setProperty('--background', colors.bg);
      root.style.setProperty('--foreground', colors.text);
      root.style.setProperty('--muted', colors.bgSecondary);
      root.style.setProperty('--muted-foreground', colors.textMuted);
      root.style.setProperty('--accent', colors.hover);
      root.style.setProperty('--accent-foreground', colors.text);
      root.style.setProperty('--border', colors.border);
      root.style.setProperty('--input', colors.borderTertiary);
      root.style.setProperty('--ring', colors.primary);
    }
  }, [darkMode, colors]);

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

  // Function Calling Settings State
  const [functionCallingEnabled, setFunctionCallingEnabled] = React.useState(true);

  // Selected Tools State - initialize with all abilities selected by default
  const [selectedTools, setSelectedTools] = React.useState(() => {
    if (typeof fluentMcpAgent !== "undefined" && fluentMcpAgent.abilities?.length) {
      const toolNames = fluentMcpAgent.abilities.map(tool => tool.function?.name).filter(Boolean);
      return new Set(toolNames);
    }
    return new Set();
  });

  // Tool Search State
  const [toolSearchQuery, setToolSearchQuery] = React.useState('');

  // Extract category from tool name
  const getToolCategory = React.useCallback((toolName) => {
    if (!toolName) return 'others';
    const parts = toolName.split('/');
    return parts.length > 1 ? parts[0] : 'others';
  }, []);

  // Get all available categories from tools
  const getAvailableCategories = React.useMemo(() => {
    if (typeof fluentMcpAgent === "undefined" || !fluentMcpAgent.abilities?.length) {
      return [];
    }
    const categories = new Set();
    fluentMcpAgent.abilities.forEach(tool => {
      const toolName = tool.function?.name;
      if (toolName) {
        const category = toolName.includes('/') ? toolName.split('/')[0] : 'others';
        categories.add(category);
      }
    });
    return Array.from(categories).sort();
  }, [typeof fluentMcpAgent !== "undefined" ? fluentMcpAgent.abilities?.length : 0]);

  // Selected Categories State - initialize with all categories selected
  const [selectedCategories, setSelectedCategories] = React.useState(() => {
    return new Set(getAvailableCategories);
  });

  // Update selectedCategories when availableCategories change
  React.useEffect(() => {
    if (getAvailableCategories.length > 0) {
      setSelectedCategories(new Set(getAvailableCategories));
    }
  }, [getAvailableCategories.length]);

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
    headers: {
      "X-WP-Nonce":
        typeof fluentMcpAgent !== "undefined"
          ? fluentMcpAgent.nonce
          : undefined
    },
    body: {
      provider: selectedProvider,
      model: selectedModel,
      ragSettings: ragSettings.enabled ? ragSettings : undefined,
      functionCallingEnabled: functionCallingEnabled,
      tools: functionCallingEnabled ? (() => {
        const tools = [];
        
        // Add client-side tools
        tools.push(
          {
            type: "function",
            function: {
              name: "WaitUntilToolExecuted",
              description: "Shows tool execution progress"
            }
          },
          {
            type: "function",
            function: {
              name: "ConfirmToolExecution",
              description: "Confirms tool execution"
            }
          }
        );
        
        // Add selected abilities from server
        if (typeof fluentMcpAgent !== "undefined" && fluentMcpAgent.abilities?.length) {
          fluentMcpAgent.abilities.forEach(tool => {
            if (tool.function?.name && selectedTools.has(tool.function.name)) {
              // Ensure properties is an object, not an array
              const normalizedTool = { ...tool };
              if (normalizedTool.function?.parameters?.properties) {
                const props = normalizedTool.function.parameters.properties;
                // If properties is an array, convert it to an object
                if (Array.isArray(props)) {
                  normalizedTool.function.parameters.properties = {};
                } else if (props && typeof props === 'object') {
                  // Ensure it's a plain object, not an array-like object
                  normalizedTool.function.parameters.properties = { ...props };
                }
              } else if (normalizedTool.function?.parameters && !normalizedTool.function.parameters.properties) {
                // Ensure properties exists as an object
                normalizedTool.function.parameters.properties = {};
              }
              tools.push(normalizedTool);
            }
          });
        }
        
        return tools.length > 0 ? tools : undefined;
      })() : undefined
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

  const handleToolToggle = (toolName) => {
    setSelectedTools(prev => {
      const newSet = new Set(prev);
      if (newSet.has(toolName)) {
        newSet.delete(toolName);
      } else {
        newSet.add(toolName);
      }
      return newSet;
    });
  };

  const handleSelectAllTools = () => {
    if (typeof fluentMcpAgent !== "undefined" && fluentMcpAgent.abilities?.length) {
      const allToolNames = fluentMcpAgent.abilities
        .map(tool => tool.function?.name)
        .filter(Boolean);
      setSelectedTools(new Set(allToolNames));
    }
  };

  const handleDeselectAllTools = () => {
    setSelectedTools(new Set());
  };

  const handleCategoryToggle = (category) => {
    setSelectedCategories(prev => {
      const newSet = new Set(prev);
      if (newSet.has(category)) {
        newSet.delete(category);
      } else {
        newSet.add(category);
      }
      return newSet;
    });
  };

  const handleSelectAllCategories = () => {
    setSelectedCategories(new Set(getAvailableCategories));
  };

  const handleDeselectAllCategories = () => {
    setSelectedCategories(new Set());
  };

  return (
    <TooltipProvider>
      <AssistantRuntimeProvider runtime={runtime}>
        <div 
          data-theme={darkMode ? 'dark' : 'light'}
          style={{
            display: 'flex',
            height: 'calc(100vh - 32px)',
            backgroundColor: colors.bg,
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            overflow: 'hidden',
            position: 'relative',
            color: colors.text,
            transition: 'background-color 0.3s ease, color 0.3s ease'
        }}>
        {/* Left Sidebar */}
        <div
        style={{
            width: sidebarOpen ? "272px" : "0",
            minWidth: sidebarOpen ? "272px" : "0",
            backgroundColor: colors.bg,
            transition: "all 0.3s ease",
            overflow: "hidden",
            display: "flex",
            flexDirection: "column",
            borderRight: `1px solid ${colors.border}`,
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
                backgroundColor: colors.bg,
                padding: "0 20px",
                borderBottom: `1px solid ${colors.border}`,
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
                    color: colors.text,
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
                        backgroundColor: colors.bgSecondary,
                        borderRadius: "8px",
                        border: `1px solid ${colors.borderSecondary}`,
                        display: "flex",
                        flexDirection: "column",
                        gap: "12px",
                        color: colors.textTertiary,
                        fontSize: "13px",
                        lineHeight: "1.6",
                    }}
                >
                    <div style={{ display: "flex", alignItems: "center", gap: "8px" }}>
                    <MessageSquareText size={18} style={{ color: colors.textMuted, flexShrink: 0 }} />
                    <div style={{ fontWeight: 600, color: colors.text }}>
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
          backgroundColor: colors.bg,
          overflow: 'hidden'
        }}>
          {/* Header */}
          <header style={{
            height: '64px',
            borderBottom: `1px solid ${colors.border}`,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            padding: '0 24px',
            backgroundColor: colors.bg,
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
                  color: colors.textTertiary,
                  padding: 0
                }}
                onMouseEnter={(e) => e.currentTarget.style.backgroundColor = colors.hover}
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
                      border: `1px solid ${colors.borderTertiary}`,
                      borderRadius: '8px',
                      backgroundColor: colors.bg,
                      cursor: 'pointer',
                      fontSize: '14px',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '8px',
                      color: colors.text,
                      fontWeight: '500',
                      transition: 'all 0.2s',
                      minWidth: '120px'
                    }}
                    onMouseEnter={(e) => {
                      e.currentTarget.style.backgroundColor = colors.hover;
                      e.currentTarget.style.borderColor = colors.borderSecondary;
                    }}
                    onMouseLeave={(e) => {
                      e.currentTarget.style.backgroundColor = colors.bg;
                      e.currentTarget.style.borderColor = colors.borderTertiary;
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
                      backgroundColor: colors.bg,
                      border: `1px solid ${colors.borderTertiary}`,
                      borderRadius: '8px',
                      boxShadow: darkMode ? '0 4px 12px rgba(0,0,0,0.3)' : '0 4px 12px rgba(0,0,0,0.1)',
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
                            color: colors.text,
                            backgroundColor: selectedProvider === provider ? colors.hover : colors.bg,
                            transition: 'background-color 0.2s'
                          }}
                          onMouseEnter={(e) => e.currentTarget.style.backgroundColor = colors.hover}
                          onMouseLeave={(e) => {
                            if (selectedProvider !== provider) {
                              e.currentTarget.style.backgroundColor = colors.bg;
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
                        border: `1px solid ${colors.borderTertiary}`,
                        borderRadius: '8px',
                        backgroundColor: colors.bg,
                        cursor: 'pointer',
                        fontSize: '14px',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '8px',
                        color: colors.text,
                        fontWeight: '500',
                        transition: 'all 0.2s',
                        minWidth: '140px'
                        }}
                        onMouseEnter={(e) => {
                        e.currentTarget.style.backgroundColor = colors.hover;
                        e.currentTarget.style.borderColor = colors.borderSecondary;
                        }}
                        onMouseLeave={(e) => {
                        e.currentTarget.style.backgroundColor = colors.bg;
                        e.currentTarget.style.borderColor = colors.borderTertiary;
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
                        backgroundColor: colors.bg,
                        border: `1px solid ${colors.borderTertiary}`,
                        borderRadius: '8px',
                        boxShadow: darkMode ? '0 4px 12px rgba(0,0,0,0.3)' : '0 4px 12px rgba(0,0,0,0.1)',
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
                                color: colors.text,
                                backgroundColor: selectedModel === model ? colors.hover : colors.bg,
                                transition: 'background-color 0.2s'
                            }}
                            onMouseEnter={(e) => e.currentTarget.style.backgroundColor = colors.hover}
                            onMouseLeave={(e) => {
                                if (selectedModel !== model) {
                                e.currentTarget.style.backgroundColor = colors.bg;
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

            <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
              {/* Dark Mode Toggle */}
              <button
                onClick={() => setDarkMode(!darkMode)}
                style={{
                  width: '36px',
                  height: '36px',
                  border: 'none',
                  borderRadius: '6px',
                  backgroundColor: 'transparent',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  transition: 'background-color 0.2s',
                  color: colors.textTertiary,
                  padding: 0
                }}
                onMouseEnter={(e) => e.currentTarget.style.backgroundColor = colors.hover}
                onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'transparent'}
                title={darkMode ? "Switch to Light Mode" : "Switch to Dark Mode"}
              >
                {darkMode ? <Sun size={20} /> : <Moon size={20} />}
              </button>

              {/* Settings Button */}
              <button
                onClick={() => setSettingsSidebarOpen(!settingsSidebarOpen)}
                style={{
                  width: '36px',
                  height: '36px',
                  border: 'none',
                  borderRadius: '6px',
                  backgroundColor: settingsSidebarOpen ? colors.hover : 'transparent',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  transition: 'background-color 0.2s',
                  color: colors.textTertiary,
                  padding: 0
                }}
                onMouseEnter={(e) => e.currentTarget.style.backgroundColor = colors.hover}
                onMouseLeave={(e) => {
                  if (!settingsSidebarOpen) {
                    e.currentTarget.style.backgroundColor = 'transparent';
                  }
                }}
                title="Settings"
              >
                <Settings size={20} />
              </button>
            </div>
          </header>

          {/* Chat Area */}
          <div style={{
            flex: 1,
            overflowY: 'auto',
            backgroundColor: colors.bg,
            position: 'relative'
          }}>
            <style>{`
              [data-theme="${darkMode ? 'dark' : 'light'}"] .aui-thread-root,
              [data-theme="${darkMode ? 'dark' : 'light'}"] .aui-thread-viewport,
              [data-theme="${darkMode ? 'dark' : 'light'}"] .aui-thread-viewport-footer {
                background-color: ${colors.bg} !important;
                color: ${colors.text} !important;
              }
              [data-theme="${darkMode ? 'dark' : 'light'}"] .aui-assistant-message-content,
              [data-theme="${darkMode ? 'dark' : 'light'}"] .aui-user-message-content {
                color: ${colors.text} !important;
              }
              [data-theme="${darkMode ? 'dark' : 'light'}"] .text-foreground {
                color: ${colors.text} !important;
              }
              [data-theme="${darkMode ? 'dark' : 'light'}"] .text-muted-foreground {
                color: ${colors.textTertiary} !important;
              }
              [data-theme="${darkMode ? 'dark' : 'light'}"] .bg-background {
                background-color: ${colors.bg} !important;
              }
            `}</style>
            <Thread welcomeMessage={null} />
          </div>
        </div>

        {/* Right Settings Sidebar */}
        <div
          style={{
            width: settingsSidebarOpen ? "320px" : "0",
            minWidth: settingsSidebarOpen ? "320px" : "0",
            backgroundColor: colors.bg,
            transition: "all 0.3s ease",
            overflow: "hidden",
            display: "flex",
            flexDirection: "column",
            borderLeft: `1px solid ${colors.border}`,
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
                backgroundColor: colors.bg,
                padding: "0 20px",
                borderBottom: `1px solid ${colors.border}`,
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
                  color: colors.text,
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
                  color: colors.textTertiary,
                  padding: 0
                }}
                onMouseEnter={(e) => e.currentTarget.style.backgroundColor = colors.hover}
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
              {/* Dark Mode Settings Section */}
              <div style={{ marginBottom: '24px' }}>
                <div style={{
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  marginBottom: '16px',
                  paddingBottom: '12px',
                  borderBottom: `1px solid ${colors.borderSecondary}`
                }}>
                  <div style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px',
                    fontWeight: 600,
                    fontSize: '14px',
                    color: colors.text,
                  }}>
                    {darkMode ? <Sun size={16} /> : <Moon size={16} />}
                    Dark Mode
                  </div>
                  <label style={{ position: 'relative', display: 'inline-block', width: '44px', height: '24px', cursor: 'pointer' }}>
                    <input
                      type="checkbox"
                      checked={darkMode}
                      onChange={(e) => setDarkMode(e.target.checked)}
                      style={{ opacity: 0, width: 0, height: 0 }}
                    />
                    <span style={{
                      position: 'absolute',
                      cursor: 'pointer',
                      top: 0,
                      left: 0,
                      right: 0,
                      bottom: 0,
                      backgroundColor: darkMode ? colors.primary : '#8c8f94',
                      transition: '.3s',
                      borderRadius: '24px'
                    }}></span>
                    <span style={{
                      position: 'absolute',
                      content: '',
                      height: '18px',
                      width: '18px',
                      left: darkMode ? '23px' : '3px',
                      bottom: '3px',
                      backgroundColor: 'white',
                      transition: '.3s',
                      borderRadius: '50%'
                    }}></span>
                  </label>
                </div>
                <p style={{ margin: 0, fontSize: '12px', color: colors.textTertiary, lineHeight: '1.5' }}>
                  Switch between light and dark mode for a more comfortable viewing experience.
                </p>
              </div>

              {/* Function Calling Settings Section */}
              <div style={{ marginBottom: '24px' }}>
                <div style={{
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  marginBottom: '16px',
                  paddingBottom: '12px',
                  borderBottom: `1px solid ${colors.borderSecondary}`
                }}>
                  <div style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px',
                    fontWeight: 600,
                    fontSize: '14px',
                    color: colors.text,
                  }}>
                    <Layers size={16} />
                    Function Calling
                  </div>
                  <label style={{ position: 'relative', display: 'inline-block', width: '44px', height: '24px', cursor: 'pointer' }}>
                    <input
                      type="checkbox"
                      checked={functionCallingEnabled}
                      onChange={(e) => setFunctionCallingEnabled(e.target.checked)}
                      style={{ opacity: 0, width: 0, height: 0 }}
                    />
                    <span style={{
                      position: 'absolute',
                      cursor: 'pointer',
                      top: 0,
                      left: 0,
                      right: 0,
                      bottom: 0,
                      backgroundColor: functionCallingEnabled ? colors.primary : '#8c8f94',
                      transition: '.3s',
                      borderRadius: '24px'
                    }}></span>
                    <span style={{
                      position: 'absolute',
                      content: '',
                      height: '18px',
                      width: '18px',
                      left: functionCallingEnabled ? '23px' : '3px',
                      bottom: '3px',
                      backgroundColor: 'white',
                      transition: '.3s',
                      borderRadius: '50%'
                    }}></span>
                  </label>
                </div>
                <p style={{ margin: 0, fontSize: '12px', color: colors.textTertiary, lineHeight: '1.5' }}>
                  Enable function calling to allow the AI to use tools and abilities. When disabled, the AI will only respond with text.
                </p>
              </div>

              {/* Available Tools Section - Only show when function calling is enabled */}
              {functionCallingEnabled && typeof fluentMcpAgent !== "undefined" && fluentMcpAgent.abilities?.length > 0 && (
                <div style={{ marginBottom: '24px' }}>
                  <div style={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    marginBottom: '12px',
                    paddingBottom: '12px',
                    borderBottom: `1px solid ${colors.borderSecondary}`
                  }}>
                    <div style={{
                      display: 'flex',
                      alignItems: 'center',
                      gap: '8px',
                      fontWeight: 600,
                      fontSize: '14px',
                      color: colors.text,
                    }}>
                      <Brain size={16} />
                      Available Tools
                    </div>
                    <div style={{ display: 'flex', gap: '8px' }}>
                      <button
                        onClick={handleSelectAllTools}
                        style={{
                          padding: '4px 8px',
                          fontSize: '11px',
                          color: colors.primary,
                          background: 'transparent',
                          border: `1px solid ${colors.primary}`,
                          borderRadius: '4px',
                          cursor: 'pointer',
                          fontWeight: 500
                        }}
                        onMouseEnter={(e) => {
                          e.currentTarget.style.backgroundColor = darkMode ? colors.bgTertiary : '#f0f6fc';
                        }}
                        onMouseLeave={(e) => {
                          e.currentTarget.style.backgroundColor = 'transparent';
                        }}
                      >
                        Select All
                      </button>
                      <button
                        onClick={handleDeselectAllTools}
                        style={{
                          padding: '4px 8px',
                          fontSize: '11px',
                          color: colors.textTertiary,
                          background: 'transparent',
                          border: `1px solid ${colors.borderTertiary}`,
                          borderRadius: '4px',
                          cursor: 'pointer',
                          fontWeight: 500
                        }}
                        onMouseEnter={(e) => {
                          e.currentTarget.style.backgroundColor = colors.hover;
                        }}
                        onMouseLeave={(e) => {
                          e.currentTarget.style.backgroundColor = 'transparent';
                        }}
                      >
                        Deselect All
                      </button>
                    </div>
                  </div>
                  
                  {/* Search Input */}
                  <div style={{ marginBottom: '12px', position: 'relative' }}>
                    <Search 
                      size={16} 
                      style={{
                        position: 'absolute',
                        left: '12px',
                        top: '50%',
                        transform: 'translateY(-50%)',
                        color: colors.textMuted,
                        pointerEvents: 'none'
                      }}
                    />
                    <input
                      type="text"
                      placeholder="Search tools..."
                      value={toolSearchQuery}
                      onChange={(e) => setToolSearchQuery(e.target.value)}
                      style={{
                        width: '100%',
                        padding: '8px 12px 8px 36px',
                        border: `1px solid ${colors.borderTertiary}`,
                        borderRadius: '6px',
                        fontSize: '13px',
                        outline: 'none',
                        transition: 'border-color 0.2s',
                        backgroundColor: colors.bg,
                        color: colors.text
                      }}
                      onFocus={(e) => e.target.style.borderColor = colors.primary}
                      onBlur={(e) => e.target.style.borderColor = colors.borderTertiary}
                    />
                  </div>

                  {/* Category Filter */}
                  {getAvailableCategories.length > 0 && (
                    <div style={{ marginBottom: '12px' }}>
                      <div style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        marginBottom: '8px'
                      }}>
                        <div style={{
                          fontSize: '12px',
                          fontWeight: 500,
                          color: colors.textSecondary
                        }}>
                          Categories
                        </div>
                        <div style={{ display: 'flex', gap: '4px' }}>
                          <button
                            onClick={handleSelectAllCategories}
                            style={{
                              padding: '2px 6px',
                              fontSize: '10px',
                              color: colors.primary,
                              background: 'transparent',
                              border: `1px solid ${colors.primary}`,
                              borderRadius: '3px',
                              cursor: 'pointer',
                              fontWeight: 500
                            }}
                            onMouseEnter={(e) => {
                              e.currentTarget.style.backgroundColor = darkMode ? colors.bgTertiary : '#f0f6fc';
                            }}
                            onMouseLeave={(e) => {
                              e.currentTarget.style.backgroundColor = 'transparent';
                            }}
                          >
                            All
                          </button>
                          <button
                            onClick={handleDeselectAllCategories}
                            style={{
                              padding: '2px 6px',
                              fontSize: '10px',
                              color: colors.textTertiary,
                              background: 'transparent',
                              border: `1px solid ${colors.borderTertiary}`,
                              borderRadius: '3px',
                              cursor: 'pointer',
                              fontWeight: 500
                            }}
                            onMouseEnter={(e) => {
                              e.currentTarget.style.backgroundColor = colors.hover;
                            }}
                            onMouseLeave={(e) => {
                              e.currentTarget.style.backgroundColor = 'transparent';
                            }}
                          >
                            None
                          </button>
                        </div>
                      </div>
                      <div style={{
                        display: 'flex',
                        flexWrap: 'wrap',
                        gap: '6px'
                      }}>
                        {getAvailableCategories.map(category => {
                          const isSelected = selectedCategories.has(category);
                          return (
                            <button
                              key={category}
                              onClick={() => handleCategoryToggle(category)}
                              style={{
                                padding: '4px 10px',
                                fontSize: '11px',
                                color: isSelected ? '#ffffff' : colors.textSecondary,
                                background: isSelected ? colors.primary : colors.bgTertiary,
                                border: `1px solid ${isSelected ? colors.primary : colors.borderTertiary}`,
                                borderRadius: '4px',
                                cursor: 'pointer',
                                fontWeight: 500,
                                transition: 'all 0.2s'
                              }}
                              onMouseEnter={(e) => {
                                if (!isSelected) {
                                  e.currentTarget.style.backgroundColor = colors.hover;
                                }
                              }}
                              onMouseLeave={(e) => {
                                if (!isSelected) {
                                  e.currentTarget.style.backgroundColor = colors.bgTertiary;
                                }
                              }}
                            >
                              {category}
                            </button>
                          );
                        })}
                      </div>
                    </div>
                  )}

                  <div style={{
                    height: '200px',
                    overflowY: 'auto',
                    border: `1px solid ${colors.borderSecondary}`,
                    borderRadius: '6px',
                    padding: '8px',
                    backgroundColor: colors.bgSecondary
                  }}>
                    {(() => {
                      // Filter tools based on category and search query
                      const filteredTools = fluentMcpAgent.abilities.filter(tool => {
                        const toolName = tool.function?.name || '';
                        const toolCategory = getToolCategory(toolName);
                        
                        // Category filter
                        if (!selectedCategories.has(toolCategory)) {
                          return false;
                        }
                        
                        // Search filter
                        if (toolSearchQuery.trim()) {
                          const toolDescription = tool.function?.description || '';
                          const searchLower = toolSearchQuery.toLowerCase();
                          return toolName.toLowerCase().includes(searchLower) || 
                                 toolDescription.toLowerCase().includes(searchLower);
                        }
                        
                        return true;
                      });

                      if (filteredTools.length === 0) {
                        return (
                          <div style={{
                            padding: '20px',
                            textAlign: 'center',
                            color: colors.textTertiary,
                            fontSize: '13px'
                          }}>
                            No tools found matching "{toolSearchQuery}"
                          </div>
                        );
                      }

                      return filteredTools.map((tool, index) => {
                        const toolName = tool.function?.name;
                        const toolDescription = tool.function?.description || 'No description';
                        const isSelected = toolName ? selectedTools.has(toolName) : false;
                        
                        if (!toolName) return null;
                      
                      return (
                        <div
                          key={toolName}
                          style={{
                            display: 'flex',
                            alignItems: 'flex-start',
                            gap: '10px',
                            padding: '10px',
                            marginBottom: index < filteredTools.length - 1 ? '8px' : 0,
                            borderRadius: '4px',
                            backgroundColor: isSelected ? (darkMode ? colors.bgTertiary : '#f0f6fc') : colors.bg,
                            border: `1px solid ${isSelected ? colors.primary : colors.borderSecondary}`,
                            transition: 'all 0.2s'
                          }}
                        >
                          <label style={{ 
                            position: 'relative', 
                            display: 'inline-block', 
                            width: '36px', 
                            height: '20px', 
                            cursor: 'pointer',
                            flexShrink: 0,
                            marginTop: '2px'
                          }}>
                            <input
                              type="checkbox"
                              checked={isSelected}
                              onChange={() => handleToolToggle(toolName)}
                              style={{ opacity: 0, width: 0, height: 0 }}
                            />
                            <span style={{
                              position: 'absolute',
                              cursor: 'pointer',
                              top: 0,
                              left: 0,
                              right: 0,
                              bottom: 0,
                              backgroundColor: isSelected ? colors.primary : colors.borderTertiary,
                              transition: '.2s',
                              borderRadius: '20px'
                            }}></span>
                            <span style={{
                              position: 'absolute',
                              height: '16px',
                              width: '16px',
                              left: isSelected ? '18px' : '2px',
                              bottom: '2px',
                              backgroundColor: 'white',
                              transition: '.2s',
                              borderRadius: '50%'
                            }}></span>
                          </label>
                          <div style={{ flex: 1, minWidth: 0 }}>
                            <div style={{
                              fontWeight: 600,
                              fontSize: '13px',
                              color: colors.text,
                              marginBottom: '4px'
                            }}>
                              {toolName}
                            </div>
                            <div style={{
                              fontSize: '12px',
                              color: colors.textTertiary,
                              lineHeight: '1.4'
                            }}>
                              {toolDescription}
                            </div>
                          </div>
                        </div>
                      );
                    });
                    })()}
                  </div>
                  <p style={{ margin: '8px 0 0', fontSize: '11px', color: colors.textTertiary, lineHeight: '1.4' }}>
                    {(() => {
                      // Calculate filtered count based on both category and search
                      const filteredCount = fluentMcpAgent.abilities.filter(tool => {
                        const toolName = tool.function?.name || '';
                        const toolCategory = getToolCategory(toolName);
                        
                        // Category filter
                        if (!selectedCategories.has(toolCategory)) {
                          return false;
                        }
                        
                        // Search filter
                        if (toolSearchQuery.trim()) {
                          const toolDescription = tool.function?.description || '';
                          const searchLower = toolSearchQuery.toLowerCase();
                          return toolName.toLowerCase().includes(searchLower) || 
                                 toolDescription.toLowerCase().includes(searchLower);
                        }
                        
                        return true;
                      }).length;
                      
                      const hasFilters = toolSearchQuery.trim() || selectedCategories.size < getAvailableCategories.length;
                      return `${selectedTools.size} of ${fluentMcpAgent.abilities.length} tools selected${hasFilters ? ` (${filteredCount} shown)` : ''}`;
                    })()}
                  </p>
                </div>
              )}

              {/* RAG Settings Section */}
              <div style={{ marginBottom: '24px' }}>
                <div style={{
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  marginBottom: '16px',
                  paddingBottom: '12px',
                  borderBottom: `1px solid ${colors.borderSecondary}`
                }}>
                  <div style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '8px',
                    fontWeight: 600,
                    fontSize: '14px',
                    color: colors.text,
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
                      backgroundColor: ragSettings.enabled ? colors.primary : '#8c8f94',
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
                      color: colors.textSecondary,
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
                        border: `1px solid ${colors.borderTertiary}`,
                        borderRadius: '6px',
                        fontSize: '14px',
                        outline: 'none',
                        backgroundColor: colors.bg,
                        color: colors.text
                      }}
                      onFocus={(e) => e.target.style.borderColor = colors.primary}
                      onBlur={(e) => e.target.style.borderColor = colors.borderTertiary}
                    />
                    <p style={{ margin: '4px 0 0', fontSize: '12px', color: colors.textTertiary }}>
                      Number of tokens per chunk
                    </p>
                  </div>

                  {/* Chunk Overlap */}
                  <div style={{ marginBottom: '16px' }}>
                    <label style={{
                      display: 'block',
                      fontSize: '13px',
                      fontWeight: 500,
                      color: colors.textSecondary,
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
                        border: `1px solid ${colors.borderTertiary}`,
                        borderRadius: '6px',
                        fontSize: '14px',
                        outline: 'none',
                        backgroundColor: colors.bg,
                        color: colors.text
                      }}
                      onFocus={(e) => e.target.style.borderColor = colors.primary}
                      onBlur={(e) => e.target.style.borderColor = colors.borderTertiary}
                    />
                    <p style={{ margin: '4px 0 0', fontSize: '12px', color: colors.textTertiary }}>
                      Overlapping tokens between chunks
                    </p>
                  </div>

                  {/* Top K */}
                  <div style={{ marginBottom: '16px' }}>
                    <label style={{
                      display: 'block',
                      fontSize: '13px',
                      fontWeight: 500,
                      color: colors.textSecondary,
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
                        border: `1px solid ${colors.borderTertiary}`,
                        borderRadius: '6px',
                        fontSize: '14px',
                        outline: 'none',
                        backgroundColor: colors.bg,
                        color: colors.text
                      }}
                      onFocus={(e) => e.target.style.borderColor = colors.primary}
                      onBlur={(e) => e.target.style.borderColor = colors.borderTertiary}
                    />
                    <p style={{ margin: '4px 0 0', fontSize: '12px', color: colors.textTertiary }}>
                      Number of similar chunks to retrieve
                    </p>
                  </div>

                  {/* Similarity Threshold */}
                  <div style={{ marginBottom: '16px' }}>
                    <label style={{
                      display: 'block',
                      fontSize: '13px',
                      fontWeight: 500,
                      color: colors.textSecondary,
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
                        border: `1px solid ${colors.borderTertiary}`,
                        borderRadius: '6px',
                        fontSize: '14px',
                        outline: 'none',
                        backgroundColor: colors.bg,
                        color: colors.text
                      }}
                      onFocus={(e) => e.target.style.borderColor = colors.primary}
                      onBlur={(e) => e.target.style.borderColor = colors.borderTertiary}
                    />
                    <p style={{ margin: '4px 0 0', fontSize: '12px', color: colors.textTertiary }}>
                      Minimum similarity score (0-1)
                    </p>
                  </div>

                  {/* Embedding Model */}
                  <div style={{ marginBottom: '16px' }}>
                    <label style={{
                      display: 'block',
                      fontSize: '13px',
                      fontWeight: 500,
                      color: colors.textSecondary,
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
                        border: `1px solid ${colors.borderTertiary}`,
                        borderRadius: '6px',
                        fontSize: '14px',
                        outline: 'none',
                        backgroundColor: colors.bg,
                        color: colors.text,
                        cursor: 'pointer'
                      }}
                      onFocus={(e) => e.target.style.borderColor = colors.primary}
                      onBlur={(e) => e.target.style.borderColor = colors.borderTertiary}
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
                      color: colors.textSecondary,
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
                        border: `1px solid ${colors.borderTertiary}`,
                        borderRadius: '6px',
                        fontSize: '14px',
                        outline: 'none',
                        backgroundColor: colors.bg,
                        color: colors.text,
                        cursor: 'pointer'
                      }}
                      onFocus={(e) => e.target.style.borderColor = colors.primary}
                      onBlur={(e) => e.target.style.borderColor = colors.borderTertiary}
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