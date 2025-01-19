(function ($) {
	"use strict";

	const FCRMBackup = {
		init: function () {
			this.container = $("#fcrm-backup-container");
			if (!this.container.length) {
				return;
			}

			this.bindEvents();
			this.initializeUI();
		},

		initializeUI: function () {
			this.updateButtonStates();
			this.importData = null;
		},

		bindEvents: function () {
			// Select/Deselect All
			$("#select-all-settings").on("change", (e) => {
				const isChecked = $(e.target).prop("checked");
				$(".setting-checkbox").prop("checked", isChecked);
				this.updateButtonStates();
			});

			// Individual setting selection
			$(".setting-checkbox").on("change", () => {
				this.updateButtonStates();
			});

			// Setting item click
			$(".setting-item").on("click", (e) => {
				if (!$(e.target).is("input")) {
					const checkbox = $(e.currentTarget).find(".setting-checkbox");
					checkbox.prop("checked", !checkbox.prop("checked"));
					checkbox.trigger("change");
					e.preventDefault();
				}
			});

			// Export button
			$("#export-settings").on("click", (e) => {
				e.preventDefault();
				this.handleExport();
			});

			// Import file selection
			const fileInput = document.getElementById("import-file");
			if (fileInput) {
				fileInput.addEventListener("change", (e) => {
					this.handleFileSelect(e);
				});
			}

			// Import button
			$("#import-settings").on("click", (e) => {
				e.preventDefault();
				this.handleImport();
			});

			// Reset button
			$("#reset-settings").on("click", (e) => {
				e.preventDefault();
				this.handleReset();
			});
		},

		handleExport: function () {
			const selectedSettings = this.getSelectedSettings();

			if (selectedSettings.length === 0) {
				this.showNotice(
					"Please select at least one setting to export",
					"error"
				);
				return;
			}

			$.ajax({
				url: fcrmBackupData.ajaxurl,
				method: "POST",
				data: {
					action: "fcrm_export_settings",
					nonce: fcrmBackupData.nonce,
					settings: JSON.stringify(selectedSettings),
				},
				success: (response) => {
					if (response.success) {
						const blob = new Blob([JSON.stringify(response.data, null, 2)], {
							type: "application/json",
						});
						const url = window.URL.createObjectURL(blob);
						const a = document.createElement("a");
						a.href = url;
						a.download = `fcrm-tributes-plugin-settings-backup-${
							new Date().toISOString().split("T")[0]
						}.json`;
						document.body.appendChild(a);
						a.click();
						document.body.removeChild(a);
						window.URL.revokeObjectURL(url);

						this.showNotice("Settings exported successfully", "success");
					} else {
						this.showNotice(response.data.message || "Export failed", "error");
					}
				},
				error: (jqXHR, textStatus, errorThrown) => {
					this.showNotice("Export failed", "error");
				},
			});
		},

		handleFileSelect: function (e) {
			const file = e.target.files[0];
			if (!file) {
				return;
			}

			const reader = new FileReader();
			reader.onload = (event) => {
				try {
					const data = JSON.parse(event.target.result);
					this.importData = data;

					if (data._meta) {
						// Clear existing selections
						$(".setting-checkbox").prop("checked", false);

						// Select checkboxes for available settings
						Object.keys(data).forEach((key) => {
							if (key !== "_meta") {
								const checkbox = $(`.setting-checkbox[value="${key}"]`);
								if (checkbox.length) {
									checkbox.prop("checked", true);
								}
							}
						});

						this.updateButtonStates();

						this.showNotice(
							`File loaded from: ${data._meta.exported_from}<br>` +
								`Date: ${new Date(
									data._meta.exported_date
								).toLocaleString()}<br>` +
								`Plugin version: ${data._meta.plugin_version}`,
							"info"
						);

						$("#import-settings").prop("disabled", false);
					} else {
						this.showNotice("Invalid backup file - missing metadata", "error");
					}
				} catch (error) {
					this.showNotice("Invalid settings file format", "error");
				}
			};

			reader.onerror = (error) => {
				this.showNotice("Error reading file", "error");
			};

			reader.readAsText(file);
		},

		handleImport: function () {
			if (!this.importData) {
				this.showNotice("Please select a file to import", "error");
				return;
			}

			const selectedSettings = this.getSelectedSettings();
			if (selectedSettings.length === 0) {
				this.showNotice("Please select settings to import", "error");
				return;
			}

			if (
				!confirm(
					"Are you sure you want to import these settings? This will overwrite your current settings."
				)
			) {
				return;
			}

			$.ajax({
				url: fcrmBackupData.ajaxurl,
				method: "POST",
				data: {
					action: "fcrm_import_settings",
					nonce: fcrmBackupData.nonce,
					settings: JSON.stringify(this.importData),
					selected: JSON.stringify(selectedSettings),
				},
				success: (response) => {
					if (response.success) {
						this.showNotice(
							"Settings imported successfully. Reloading page...",
							"success"
						);
						setTimeout(() => window.location.reload(), 1500);
					} else {
						this.showNotice(response.data.message || "Import failed", "error");
					}
				},
				error: (jqXHR, textStatus, errorThrown) => {
					this.showNotice("Import failed: " + textStatus, "error");
				},
			});
		},

		handleReset: function () {
			const selectedSettings = this.getSelectedSettings();
			if (selectedSettings.length === 0) {
				this.showNotice("Please select settings to reset", "error");
				return;
			}

			if (
				!confirm(
					"Are you sure you want to reset these settings? This cannot be undone."
				)
			) {
				return;
			}

			$.ajax({
				url: fcrmBackupData.ajaxurl,
				method: "POST",
				data: {
					action: "fcrm_reset_settings",
					nonce: fcrmBackupData.nonce,
					settings: JSON.stringify(selectedSettings),
				},
				success: (response) => {
					if (response.success) {
						this.showNotice(
							"Settings reset successfully. Reloading page...",
							"success"
						);
						setTimeout(() => window.location.reload(), 1500);
					} else {
						this.showNotice(response.data.message || "Reset failed", "error");
					}
				},
				error: () => {
					this.showNotice("Reset failed", "error");
				},
			});
		},

		getSelectedSettings: function () {
			return this.container
				.find(".setting-checkbox:checked")
				.map(function () {
					return $(this).val();
				})
				.get();
		},

		updateButtonStates: function () {
			const hasSelection = this.getSelectedSettings().length > 0;
			$("#export-settings, #reset-settings").prop("disabled", !hasSelection);

			// Update select all checkbox state
			const totalCheckboxes = $(".setting-checkbox").length;
			const checkedCheckboxes = $(".setting-checkbox:checked").length;
			$("#select-all-settings").prop(
				"checked",
				totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes
			);

			// Only enable import button if we have both a file and selections
			if (this.importData) {
				$("#import-settings").prop("disabled", !hasSelection);
			}
		},

		showNotice: function (message, type = "info") {
			const notice = $("<div>")
				.addClass(`notice notice-${type} is-dismissible fcrm-backup-notice`)
				.html(`<p>${message}</p>`);

			// Remove existing notices of the same type
			this.container.find(`.notice-${type}`).remove();

			// Add new notice
			this.container.find(".notice-container").append(notice);

			// Initialize WordPress dismissible notices
			if (window.wp && window.wp.notices) {
				window.wp.notices.initialize();
			}
		},
	};

	$(document).ready(function () {
		FCRMBackup.init();
	});
})(jQuery);
