// Block Editor Registration (Editor only)
(function () {
  'use strict';

  // Check if we're in the block editor
  if (!window.wp || !window.wp.blocks || !window.wp.blockEditor) {
    console.log('Ziad API Plugin: Not in block editor context, skipping block registration');
    return;
  }

  const { registerBlockType } = wp.blocks;
  const { InspectorControls, useBlockProps } = wp.blockEditor;
  const { PanelBody, ToggleControl } = wp.components;
  const { Fragment } = wp.element;
  const { __ } = wp.i18n;

  console.log('Ziad API Plugin: Registering block...');

  try {
    registerBlockType('ziad-api-plugin/data-table', {
      apiVersion: 2,
      title: __('API Data Table', 'ziad-api-plugin'),
      description: __('Display data from external API in a table', 'ziad-api-plugin'),
      icon: 'list-view',
      category: 'widgets',
      keywords: [
        __('api', 'ziad-api-plugin'),
        __('data', 'ziad-api-plugin'),
        __('table', 'ziad-api-plugin')
      ],
      supports: {
        html: false,
        align: true,
        anchor: true,
      },
      attributes: {
        showTitle: {
          type: 'boolean',
          default: true,
        },
        showId: {
          type: 'boolean',
          default: true,
        },
        showFirstName: {
          type: 'boolean',
          default: true,
        },
        showLastName: {
          type: 'boolean',
          default: true,
        },
        showEmail: {
          type: 'boolean',
          default: true,
        },
        showDate: {
          type: 'boolean',
          default: true,
        },
      },

      edit: function (props) {
        const { attributes, setAttributes } = props;
        const { showTitle, showId, showFirstName, showLastName, showEmail, showDate } = attributes;
        
        // Use useBlockProps for proper block wrapper (WordPress 5.9+)
        const blockProps = useBlockProps ? useBlockProps({
          className: 'ziad-api-block-editor',
          style: {
            border: '1px solid #ddd',
            borderRadius: '4px',
            padding: '20px',
            backgroundColor: '#f9f9f9',
            position: 'relative'
          }
        }) : {
          className: 'ziad-api-block-editor',
          style: {
            border: '1px solid #ddd',
            borderRadius: '4px',
            padding: '20px',
            backgroundColor: '#f9f9f9',
            position: 'relative'
          }
        };

        // Count visible columns
        const visibleColumns = [showId, showFirstName, showLastName, showEmail, showDate].filter(Boolean).length;

        return wp.element.createElement(
          Fragment,
          null,
          
          // Sidebar Inspector Controls
          wp.element.createElement(
            InspectorControls,
            null,
            
            // Display Settings Panel
            wp.element.createElement(
              PanelBody,
              {
                title: __('Display Settings', 'ziad-api-plugin'),
                initialOpen: true,
              },
              wp.element.createElement(ToggleControl, {
                label: __('Show Table Title', 'ziad-api-plugin'),
                help: __('Display "API Data Table" heading above the table', 'ziad-api-plugin'),
                checked: showTitle,
                onChange: function (value) {
                  setAttributes({ showTitle: value });
                },
              })
            ),
            
            // Column Visibility Panel
            wp.element.createElement(
              PanelBody,
              {
                title: __('Column Visibility', 'ziad-api-plugin'),
                initialOpen: true,
              },
              wp.element.createElement(
                'p',
                {
                  style: {
                    marginTop: 0,
                    marginBottom: '15px',
                    fontSize: '13px',
                    color: '#666'
                  }
                },
                __('Control which columns are displayed in the table:', 'ziad-api-plugin')
              ),
              wp.element.createElement(ToggleControl, {
                label: __('Show ID Column', 'ziad-api-plugin'),
                checked: showId,
                onChange: function (value) {
                  setAttributes({ showId: value });
                },
              }),
              wp.element.createElement(ToggleControl, {
                label: __('Show First Name Column', 'ziad-api-plugin'),
                checked: showFirstName,
                onChange: function (value) {
                  setAttributes({ showFirstName: value });
                },
              }),
              wp.element.createElement(ToggleControl, {
                label: __('Show Last Name Column', 'ziad-api-plugin'),
                checked: showLastName,
                onChange: function (value) {
                  setAttributes({ showLastName: value });
                },
              }),
              wp.element.createElement(ToggleControl, {
                label: __('Show Email Column', 'ziad-api-plugin'),
                checked: showEmail,
                onChange: function (value) {
                  setAttributes({ showEmail: value });
                },
              }),
              wp.element.createElement(ToggleControl, {
                label: __('Show Date Column', 'ziad-api-plugin'),
                checked: showDate,
                onChange: function (value) {
                  setAttributes({ showDate: value });
                },
              }),
              wp.element.createElement(
                'div',
                {
                  style: {
                    marginTop: '15px',
                    padding: '10px',
                    background: '#f0f0f0',
                    borderRadius: '4px',
                    fontSize: '12px',
                    color: '#666'
                  }
                },
                wp.element.createElement(
                  'strong',
                  null,
                  __('Visible columns: ', 'ziad-api-plugin')
                ),
                visibleColumns + ' ' + __('of', 'ziad-api-plugin') + ' 5'
              )
            )
          ),
          
          // Block Preview in Editor
          wp.element.createElement(
            'div',
            blockProps,
            
            // Title (if enabled)
            showTitle &&
              wp.element.createElement(
                'h3',
                { 
                  style: { 
                    marginTop: 0,
                    color: '#333',
                    fontSize: '20px',
                    fontWeight: 600
                  } 
                },
                __('API Data Table', 'ziad-api-plugin')
              ),
            
            // Preview Box
            wp.element.createElement(
              'div',
              { 
                style: { 
                  padding: '30px 20px',
                  background: '#fff',
                  textAlign: 'center',
                  border: '2px dashed #ccc',
                  borderRadius: '4px',
                  marginTop: showTitle ? '15px' : 0
                } 
              },
              
              // Icon and Title
              wp.element.createElement(
                'p',
                { 
                  style: { 
                    fontSize: '16px', 
                    margin: '0 0 10px 0',
                    fontWeight: 600,
                    color: '#333'
                  } 
                },
                '📊 ',
                wp.element.createElement('strong', null, __('API Data Table', 'ziad-api-plugin'))
              ),
              
              // Description
              wp.element.createElement(
                'p',
                {
                  style: {
                    fontSize: '13px',
                    color: '#666',
                    margin: '0 0 15px 0'
                  }
                },
                __('Data will be displayed on the frontend', 'ziad-api-plugin')
              ),
              
              // Column visibility info
              wp.element.createElement(
                'div',
                { 
                  style: { 
                    fontSize: '12px', 
                    color: '#0073aa',
                    padding: '8px 12px',
                    background: '#f0f6fc',
                    border: '1px solid #0073aa',
                    borderRadius: '3px',
                    display: 'inline-block'
                  } 
                },
                '✓ ',
                __('Visible columns: ', 'ziad-api-plugin') + visibleColumns + ' / 5'
              ),
              
              // Disabled columns warning
              visibleColumns === 0 &&
                wp.element.createElement(
                  'div',
                  {
                    style: {
                      marginTop: '15px',
                      padding: '10px',
                      background: '#fff3cd',
                      border: '1px solid #ffc107',
                      borderRadius: '3px',
                      fontSize: '12px',
                      color: '#856404'
                    }
                  },
                  '⚠️ ',
                  __('Warning: All columns are hidden. Enable at least one column in the sidebar settings.', 'ziad-api-plugin')
                )
            )
          )
        );
      },

      save: function () {
        // Dynamic block - rendered server-side by PHP
        // Return null to indicate server-side rendering
        return null;
      },
    });

    console.log('✓ Ziad API Plugin: Block registered successfully!');
  } catch (error) {
    console.error('Ziad API Plugin: Error registering block:', error);
  }
})();