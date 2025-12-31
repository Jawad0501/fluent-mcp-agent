import React from "react";
import { AssistantRuntimeProvider } from "@assistant-ui/react";
import { useDataStreamRuntime } from "@assistant-ui/react-data-stream";
import { Thread } from "@/components/assistant-ui/thread";
import { Settings, X, ChevronDown, MessageSquare, Sparkles } from "lucide-react";
import { TooltipProvider } from "@/components/ui/tooltip";
import { ConfirmToolExecution } from "./ConfirmToolExecution";
import { WaitUntilToolExecuted } from "./WaitUntilToolExecuted";

export default function AssistantModal() {
  const [isOpen, setIsOpen] = React.useState(false);
  
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

  // Update model when provider changes
  React.useEffect(() => {
    if (fluentMcpAgent?.availableModels?.length) {
      const found = fluentMcpAgent.availableModels.find(
        (obj) => obj[selectedProvider]
      );
      if (found?.[selectedProvider]?.length) {
        setSelectedModel(found[selectedProvider][0]);
      }
    }
  }, [selectedProvider]);

  // Create runtime for assistant-ui
  const runtime = useDataStreamRuntime({
    api: "/api/chat",
    body: {
      provider: selectedProvider,
      model: selectedModel,
    },
    onError(error) {
      console.error("Assistant stream error:", error);
    },
  });

  const handleProviderChange = (provider) => {
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

  // Close modal on ESC key
  React.useEffect(() => {
    const handleEscape = (e) => {
      if (e.key === 'Escape' && isOpen) {
        setIsOpen(false);
      }
    };
    window.addEventListener('keydown', handleEscape);
    return () => window.removeEventListener('keydown', handleEscape);
  }, [isOpen]);

  // Prevent body scroll when modal is open
  React.useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => {
      document.body.style.overflow = '';
    };
  }, [isOpen]);

  return (
    <>
      {/* Floating Action Button */}
      <button
        onClick={() => setIsOpen(true)}
        style={{
          position: 'fixed',
          bottom: '24px',
          right: '24px',
          width: '56px',
          height: '56px',
          borderRadius: '50%',
          backgroundColor: '#2271b1',
          border: 'none',
          boxShadow: '0 4px 12px rgba(34, 113, 177, 0.4)',
          cursor: 'pointer',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          transition: 'all 0.3s ease',
          zIndex: 9998,
          color: '#ffffff'
        }}
        onMouseEnter={(e) => {
          e.currentTarget.style.transform = 'scale(1.1)';
          e.currentTarget.style.backgroundColor = '#135e96';
          e.currentTarget.style.boxShadow = '0 6px 16px rgba(34, 113, 177, 0.5)';
        }}
        onMouseLeave={(e) => {
          e.currentTarget.style.transform = 'scale(1)';
          e.currentTarget.style.backgroundColor = '#2271b1';
          e.currentTarget.style.boxShadow = '0 4px 12px rgba(34, 113, 177, 0.4)';
        }}
        title="Open Assistant"
      >
        <Sparkles size={24} />
      </button>

      {/* Modal Overlay */}
      {isOpen && (
        <div
          onClick={() => setIsOpen(false)}
          style={{
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            backgroundColor: 'rgba(0, 0, 0, 0.5)',
            zIndex: 9999,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            padding: '20px',
            animation: 'fadeIn 0.2s ease-out'
          }}
        >
          {/* Modal Content */}
          <TooltipProvider>
            <AssistantRuntimeProvider runtime={runtime}>
              <div
                onClick={(e) => e.stopPropagation()}
                style={{
                  width: '100%',
                  maxWidth: '900px',
                  height: '85vh',
                  maxHeight: '700px',
                  backgroundColor: '#ffffff',
                  borderRadius: '16px',
                  boxShadow: '0 20px 60px rgba(0, 0, 0, 0.3)',
                  display: 'flex',
                  flexDirection: 'column',
                  overflow: 'hidden',
                  fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                  animation: 'slideUp 0.3s ease-out'
                }}
              >
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
                  zIndex: 10,
                  flexShrink: 0
                }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                    {/* Title */}
                    <h2 style={{
                      margin: 0,
                      fontSize: '18px',
                      fontWeight: '600',
                      color: '#1d2327'
                    }}>
                      Fluent MCP Agent
                    </h2>

                    {/* Model Selector */}
                    {currentModels.length > 0 && (
                      <div style={{ position: 'relative' }}>
                        <button
                          onClick={() => setModelDropdownOpen(!modelDropdownOpen)}
                          style={{
                            padding: '6px 12px',
                            border: '1px solid #d0d0d0',
                            borderRadius: '6px',
                            backgroundColor: '#ffffff',
                            cursor: 'pointer',
                            fontSize: '13px',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '6px',
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
                          <span style={{ flex: 1, textAlign: 'left', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                            {selectedModel}
                          </span>
                          <ChevronDown size={14} style={{ flexShrink: 0 }} />
                        </button>
                        {modelDropdownOpen && (
                          <div style={{
                            position: 'absolute',
                            top: 'calc(100% + 4px)',
                            left: 0,
                            minWidth: '180px',
                            backgroundColor: '#ffffff',
                            border: '1px solid #d0d0d0',
                            borderRadius: '8px',
                            boxShadow: '0 4px 12px rgba(0,0,0,0.1)',
                            zIndex: 1000,
                            overflow: 'hidden',
                            maxHeight: '250px',
                            overflowY: 'auto'
                          }}>
                            {currentModels.map((model) => (
                              <div
                                key={model}
                                onClick={() => handleModelChange(model)}
                                style={{
                                  padding: '10px 14px',
                                  cursor: 'pointer',
                                  fontSize: '13px',
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

                    {/* Provider Selector */}
                    {fluentMcpAgent?.enabledProviders?.length > 1 && (
                      <div style={{ position: 'relative' }}>
                        <button
                          onClick={() => setProviderDropdownOpen(!providerDropdownOpen)}
                          style={{
                            padding: '6px 12px',
                            border: '1px solid #d0d0d0',
                            borderRadius: '6px',
                            backgroundColor: '#ffffff',
                            cursor: 'pointer',
                            fontSize: '13px',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '6px',
                            color: '#2d2d2d',
                            fontWeight: '500',
                            transition: 'all 0.2s',
                            minWidth: '100px'
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
                          <ChevronDown size={14} style={{ flexShrink: 0 }} />
                        </button>
                        {providerDropdownOpen && (
                          <div style={{
                            position: 'absolute',
                            top: 'calc(100% + 4px)',
                            left: 0,
                            minWidth: '140px',
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
                                  padding: '10px 14px',
                                  cursor: 'pointer',
                                  fontSize: '13px',
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
                  </div>

                  {/* Close Button */}
                  <button
                    onClick={() => setIsOpen(false)}
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
                    title="Close (Esc)"
                  >
                    <X size={20} />
                  </button>
                </header>

                {/* Chat Area */}
                <div style={{
                  flex: 1,
                  overflowY: 'auto',
                  backgroundColor: '#ffffff',
                  position: 'relative'
                }}>
                  <ConfirmToolExecution />
                  <WaitUntilToolExecuted />
                  <Thread />
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
        </div>
      )}

      {/* CSS Animations */}
      <style>{`
        @keyframes fadeIn {
          from {
            opacity: 0;
          }
          to {
            opacity: 1;
          }
        }

        @keyframes slideUp {
          from {
            transform: translateY(20px);
            opacity: 0;
          }
          to {
            transform: translateY(0);
            opacity: 1;
          }
        }
      `}</style>
    </>
  );
}