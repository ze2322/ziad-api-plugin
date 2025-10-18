// Frontend Table Loading (Frontend only)
(function() {
  'use strict';

  console.log('Ziad API Plugin: Frontend script loaded');

  function initializeTable() {
    // Check if ziadApiPlugin is defined
    if (typeof ziadApiPlugin === 'undefined') {
      console.error('Ziad API Plugin: Configuration not loaded');
      return;
    }
    
    console.log('Ziad API Plugin: Configuration loaded', ziadApiPlugin);
    
    const tableContainers = document.querySelectorAll('#ziad-api-data-table');

    if (!tableContainers.length) {
      console.log('Ziad API Plugin: No table containers found - this is normal if block is not on the page');
      return;
    }

    console.log('Ziad API Plugin: Found', tableContainers.length, 'table container(s)');

    tableContainers.forEach(function (container) {
      loadTableData(container);
    });

    // Setup refresh button (if exists on admin page)
    const refreshBtn = document.getElementById('ziad-refresh-data');
    if (refreshBtn) {
      console.log('Ziad API Plugin: Refresh button found, attaching handler');
      refreshBtn.addEventListener('click', function (e) {
        e.preventDefault();
        handleRefresh(refreshBtn, tableContainers);
      });
    }
  }

  function handleRefresh(button, containers) {
    console.log('Refresh button clicked');
    button.disabled = true;
    button.textContent = 'Refreshing...';

    const url = ziadApiPlugin.ajax_url + 
                '?action=ziad_refresh_data&nonce=' + 
                ziadApiPlugin.nonce;

    console.log('Refresh URL:', url);

    fetch(url)
      .then(res => res.json())
      .then(result => {
        console.log('Refresh response:', result);
        if (result.success) {
          containers.forEach(loadTableData);
          alert('Data refreshed successfully!');
        } else {
          alert('Error: ' + (result.data?.message || 'Unknown error'));
        }
      })
      .catch(err => {
        console.error('Refresh error:', err);
        alert('Failed to refresh data');
      })
      .finally(() => {
        button.disabled = false;
        button.textContent = 'Refresh Data';
      });
  }

  function loadTableData(container) {
    console.log('loadTableData() called for container:', container);
    
    container.innerHTML = '<div style="padding: 20px; text-align: center;">' +
                          '<span class="spinner is-active"></span> Loading data...</div>';

    const url = ziadApiPlugin.ajax_url + '?action=ziad_get_data';
    console.log('Fetching data from:', url);

    fetch(url)
      .then(res => {
        console.log('Response status:', res.status);
        console.log('Response ok:', res.ok);
        return res.json();
      })
      .then(result => {
        console.log('Raw response:', result);
        
        if (!result.success) {
          console.error('API returned success=false');
          container.innerHTML = '<p style="color: red;">API Error: ' + (result.data?.message || 'Unknown error') + '</p>';
          return;
        }
        
        if (!result.data || !result.data.data) {
          console.error('No data in response');
          container.innerHTML = '<p>No data available in response.</p>';
          return;
        }

        console.log('Data structure:', result.data);
        const data = result.data.data;
        console.log('Headers:', data.headers);
        console.log('Rows count:', data.rows ? data.rows.length : 0);
        
        renderTable(container, data);
      })
      .catch(err => {
        console.error('Fetch error:', err);
        console.error('Error details:', {
          message: err.message,
          stack: err.stack
        });
        container.innerHTML = '<p style="color: red;">Error loading data: ' + err.message + '</p>' +
                              '<p style="color: #666; font-size: 12px;">Check browser console for details.</p>';
      });
  }

  function renderTable(container, data) {
    console.log('renderTable() called');
    
    const headers = data.headers || [];
    const rows = data.rows || [];

    // Get column visibility settings from parent div data attributes
    const parentDiv = container.closest('.wp-block-ziad-api-plugin-data-table');
    const showId = parentDiv ? parentDiv.dataset.showId === '1' : true;
    const showFirstname = parentDiv ? parentDiv.dataset.showFirstname === '1' : true;
    const showLastname = parentDiv ? parentDiv.dataset.showLastname === '1' : true;
    const showEmail = parentDiv ? parentDiv.dataset.showEmail === '1' : true;
    const showDate = parentDiv ? parentDiv.dataset.showDate === '1' : true;

    console.log('Column visibility:', { showId, showFirstname, showLastname, showEmail, showDate });
    console.log('Rendering table with', headers.length, 'headers and', rows.length, 'rows');

    let html = '<table class="wp-list-table widefat fixed striped ziad-api-table" style="width: 100%; border-collapse: collapse;">';
    html += '<thead><tr>';
    
    // Only render visible column headers
    if (showId) html += '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">' + escapeHtml(headers[0] || 'ID') + '</th>';
    if (showFirstname) html += '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">' + escapeHtml(headers[1] || 'First Name') + '</th>';
    if (showLastname) html += '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">' + escapeHtml(headers[2] || 'Last Name') + '</th>';
    if (showEmail) html += '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">' + escapeHtml(headers[3] || 'Email') + '</th>';
    if (showDate) html += '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">' + escapeHtml(headers[4] || 'Date') + '</th>';
    
    html += '</tr></thead><tbody>';

    if (rows.length === 0) {
      const visibleCols = [showId, showFirstname, showLastname, showEmail, showDate].filter(Boolean).length;
      html += '<tr><td colspan="' + visibleCols + '" style="padding: 20px; text-align: center;">No data available</td></tr>';
    } else {
      rows.forEach((row, index) => {
        console.log('Rendering row', index, ':', row);
        html += '<tr>';
        
        // FIX: Handle 0 values correctly - check for undefined/null, not falsy
        if (showId) {
          const idValue = (row.id !== undefined && row.id !== null) ? row.id : '';
          html += '<td style="padding: 10px; border-bottom: 1px solid #eee;">' + escapeHtml(idValue) + '</td>';
        }
        
        if (showFirstname) {
          html += '<td style="padding: 10px; border-bottom: 1px solid #eee;">' + escapeHtml(row.fname || '') + '</td>';
        }
        
        if (showLastname) {
          html += '<td style="padding: 10px; border-bottom: 1px solid #eee;">' + escapeHtml(row.lname || '') + '</td>';
        }
        
        if (showEmail) {
          html += '<td style="padding: 10px; border-bottom: 1px solid #eee;">' + escapeHtml(row.email || '') + '</td>';
        }
        
        if (showDate) {
          html += '<td style="padding: 10px; border-bottom: 1px solid #eee;">' + escapeHtml(row.date || '') + '</td>';
        }
        
        html += '</tr>';
      });
    }

    html += '</tbody></table>';
    container.innerHTML = html;
    
    console.log('✓ Table rendered successfully!');
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
  }

  // Initialize when DOM is ready
  console.log('Setting up initialization...');
  
  if (document.readyState === 'loading') {
    console.log('DOM is still loading, adding DOMContentLoaded listener');
    document.addEventListener('DOMContentLoaded', function() {
      console.log('DOMContentLoaded event fired');
      initializeTable();
    });
  } else {
    console.log('DOM already loaded, initializing immediately');
    initializeTable();
  }
  
  console.log('=== Frontend script setup complete ===');
})();