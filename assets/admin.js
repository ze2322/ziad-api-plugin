jQuery(document).ready(function ($) {
    const AdminPage = {
        init: function () {
            this.loadData();
            this.bindEvents();
        },
        bindEvents: function () {
            $("#ziad-refresh-data").on("click", this.refreshData.bind(this));
        },
        loadData: function () {
            const $container = $("#ziad-api-data-table");
            $container.html('<span class="spinner is-active"></span> Loading data...');
            $.ajax({
                url: ziadApiPlugin.ajax_url,
                type: "GET",
                data: { action: "ziad_get_data" },
                success: function (response) {
                    if (response.success && response.data) {
                        AdminPage.renderTable(response.data);
                        $("#ziad-last-updated").text(`Last updated: ${response.timestamp}`);
                    } else {
                        $container.html("<p>No data available.</p>");
                    }
                },
                error: function () {
                    $container.html("<p>Error loading data.</p>");
                },
            });
        },
        refreshData: function (e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            $btn.prop("disabled", true).text("Refreshing...");
            $.ajax({
                url: ziadApiPlugin.ajax_url,
                type: "POST",
                data: { action: "ziad_refresh_data", nonce: ziadApiPlugin.nonce },
                success: function (response) {
                    AdminPage.loadData();
                },
                complete: function () {
                    $btn.prop("disabled", false).text("Refresh Data");
                },
            });
        },
        renderTable: function (data) {
            const $container = $("#ziad-api-data-table");
            let html = "<table class='wp-list-table widefat fixed striped'><thead><tr>";
            data.data.headers.forEach(h=>html+="<th>"+h+"</th>");
            html+="</tr></thead><tbody>";
            data.data.rows.forEach(r=>{
                html+="<tr>";
                html+=`<td>${r.id}</td>`;
                html+=`<td>${r.fname}</td>`;
                html+=`<td>${r.lname}</td>`;
                html+=`<td>${r.email}</td>`;
                html+=`<td>${r.date}</td>`;
                html+="</tr>";
            });
            html+="</tbody></table>";
            $container.html(html);
        },
    };

    if ($("#ziad-api-data-table").length) AdminPage.init();
});
