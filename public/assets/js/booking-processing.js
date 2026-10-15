(() => {
    "use strict";

    document.addEventListener("DOMContentLoaded", () => {
        const form = document.querySelector("[data-booking-processing-form]");
        const configNode = document.getElementById("bookingProcessingConfig");
        if (!form || !configNode) return;

        let config;
        try {
            config = JSON.parse(configNode.textContent || "{}");
        } catch {
            return;
        }

        const catalog = Array.isArray(config.catalog) ? config.catalog : [];
        const itemSelect = document.getElementById("storage_item");
        const sizeSelect = document.getElementById("storage_item_size");
        const itemQuantity = document.getElementById("storage_item_quantity");
        const selectedItemsNode = form.querySelector("[data-selected-items]");
        const summaryNode = form.querySelector("[data-pricing-summary]");
        const totalNode = form.querySelector("[data-estimated-total]");
        const storageBoxes = document.getElementById("additional_box_quantity");
        const advantageBoxes = document.getElementById("box_quantity");
        const selectedItems = new Map();

        const sizeIndex = new Map();
        catalog.forEach((item) => {
            (item.sizes || []).forEach((size) => {
                sizeIndex.set(Number(size.id), { item, size });
            });
        });

        (Array.isArray(config.selectedItems) ? config.selectedItems : []).forEach((requested) => {
            const sizeId = Number(requested.size_id || 0);
            const quantity = Number(requested.quantity || 0);
            const match = sizeIndex.get(sizeId);
            if (match && quantity > 0 && quantity <= 100) {
                selectedItems.set(sizeId, { ...match, quantity });
            }
        });

        const amount = (value) => {
            const parsed = Number.parseFloat(String(value ?? "0"));
            return Number.isFinite(parsed) ? parsed : 0;
        };
        const money = (value) => new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(value);
        const bookingType = () => form.querySelector('input[name="booking_type"]:checked')?.value || "summer_storage";

        const appendSummaryLine = (label, value) => {
            if (!summaryNode) return;
            const row = document.createElement("div");
            const name = document.createElement("span");
            const price = document.createElement("strong");
            name.textContent = label;
            price.textContent = money(value);
            row.append(name, price);
            summaryNode.append(row);
        };

        const renderSummary = () => {
            if (!summaryNode || !totalNode) return;
            summaryNode.replaceChildren();
            const prices = config.prices || {};

            if (bookingType() === "summer_advantage") {
                const quantity = Math.max(0, Number.parseInt(advantageBoxes?.value || "0", 10) || 0);
                const boxPrice = amount(prices.summer_advantage_box_price);
                const total = quantity * boxPrice;
                appendSummaryLine(`Summer Advantage boxes × ${quantity}`, total);
                totalNode.textContent = money(total);
                return;
            }

            const base = amount(prices.summer_storage_base_price);
            const boxQuantity = Math.max(0, Number.parseInt(storageBoxes?.value || "0", 10) || 0);
            const boxTotal = boxQuantity * amount(prices.summer_storage_additional_box_price);
            const itemsTotal = Array.from(selectedItems.values()).reduce((total, line) => total + (amount(line.size.unit_price) * line.quantity), 0);
            const summerSchool = form.querySelector('input[name="is_summer_school"]:checked')?.value === "1";
            const studyAbroad = form.querySelector('input[name="is_study_abroad"]:checked')?.value === "1";
            const summerSchoolFee = summerSchool ? amount(prices.summer_school_fee) : 0;
            const serviceSubtotal = base + boxTotal + itemsTotal + summerSchoolFee;
            const studyAbroadCharge = studyAbroad ? serviceSubtotal : 0;

            appendSummaryLine("Summer Storage base package", base);
            if (boxQuantity > 0) appendSummaryLine(`Additional boxes × ${boxQuantity}`, boxTotal);
            if (itemsTotal > 0) appendSummaryLine("Additional storage items", itemsTotal);
            if (summerSchoolFee > 0) appendSummaryLine("Summer school fee", summerSchoolFee);
            if (studyAbroadCharge > 0) appendSummaryLine("Study-abroad second semester", studyAbroadCharge);
            totalNode.textContent = money(serviceSubtotal + studyAbroadCharge);
        };

        const renderSelectedItems = () => {
            if (!selectedItemsNode) return;
            selectedItemsNode.replaceChildren();

            if (selectedItems.size === 0) {
                const empty = document.createElement("div");
                empty.className = "booking-empty-state";
                empty.textContent = "No additional items selected.";
                selectedItemsNode.append(empty);
                renderSummary();
                return;
            }

            Array.from(selectedItems.entries()).forEach(([sizeId, line], index) => {
                const row = document.createElement("div");
                row.className = "booking-selected-item";

                const description = document.createElement("div");
                const title = document.createElement("strong");
                const detail = document.createElement("small");
                title.textContent = line.item.name;
                detail.textContent = `${line.size.name} · ${line.quantity} × ${money(amount(line.size.unit_price))}`;
                description.append(title, detail);

                const actions = document.createElement("div");
                const lineTotal = document.createElement("strong");
                const remove = document.createElement("button");
                lineTotal.textContent = money(amount(line.size.unit_price) * line.quantity);
                remove.type = "button";
                remove.className = "btn btn-sm btn-outline-danger";
                remove.textContent = "Remove";
                remove.addEventListener("click", () => {
                    selectedItems.delete(sizeId);
                    renderSelectedItems();
                });
                actions.append(lineTotal, remove);

                const sizeInput = document.createElement("input");
                sizeInput.type = "hidden";
                sizeInput.name = `items[${index}][size_id]`;
                sizeInput.value = String(sizeId);
                const quantityInput = document.createElement("input");
                quantityInput.type = "hidden";
                quantityInput.name = `items[${index}][quantity]`;
                quantityInput.value = String(line.quantity);

                row.append(description, actions, sizeInput, quantityInput);
                selectedItemsNode.append(row);
            });

            renderSummary();
        };

        const syncSizes = () => {
            if (!itemSelect || !sizeSelect) return;
            sizeSelect.setCustomValidity("");
            const item = catalog.find((candidate) => Number(candidate.id) === Number(itemSelect.value));
            sizeSelect.replaceChildren();
            const prompt = document.createElement("option");
            prompt.value = "";
            prompt.textContent = item ? "Choose a size" : "Choose an item first";
            sizeSelect.append(prompt);
            sizeSelect.disabled = !item;
            (item?.sizes || []).forEach((size) => {
                const option = document.createElement("option");
                option.value = String(size.id);
                option.textContent = `${size.name} — ${money(amount(size.unit_price))}`;
                sizeSelect.append(option);
            });
        };

        const syncService = () => {
            const current = bookingType();
            form.querySelectorAll("[data-service-panel]").forEach((panel) => {
                panel.hidden = panel.dataset.servicePanel !== current;
            });
            form.querySelectorAll(".service-choice").forEach((choice) => {
                choice.classList.toggle("is-selected", choice.querySelector("input")?.checked === true);
            });
            if (advantageBoxes) advantageBoxes.required = current === "summer_advantage";
            renderSummary();
        };

        form.addEventListener("click", (event) => {
            if (!(event.target instanceof Element)) return;
            const button = event.target.closest("[data-quantity-action]");
            if (!button || !form.contains(button)) return;

            const stepper = button.closest("[data-quantity-stepper]");
            const input = stepper?.querySelector('input[type="number"]');
            if (!input || input.disabled) return;

            const minimum = Number.parseInt(input.min || "0", 10);
            const maximum = Number.parseInt(input.max || "100", 10);
            const current = Number.parseInt(input.value, 10);
            const safeCurrent = Number.isInteger(current) ? current : minimum;
            const direction = button.dataset.quantityAction === "increment" ? 1 : -1;
            const nextValue = Math.min(maximum, Math.max(minimum, safeCurrent + direction));

            input.value = String(nextValue);
            input.dispatchEvent(new Event("input", { bubbles: true }));
            input.dispatchEvent(new Event("change", { bubbles: true }));
        });

        itemSelect?.addEventListener("change", syncSizes);
        form.querySelector("[data-add-storage-item]")?.addEventListener("click", () => {
            const sizeId = Number(sizeSelect?.value || 0);
            const quantity = Number.parseInt(itemQuantity?.value || "0", 10);
            const match = sizeIndex.get(sizeId);
            if (!match || !Number.isInteger(quantity) || quantity < 1 || quantity > 100) {
                if (sizeSelect && !match) {
                    sizeSelect.setCustomValidity("Choose an available item size.");
                    sizeSelect.reportValidity();
                }
                if (itemQuantity && (!Number.isInteger(quantity) || quantity < 1 || quantity > 100)) itemQuantity.reportValidity();
                return;
            }
            sizeSelect?.setCustomValidity("");
            const existing = selectedItems.get(sizeId);
            const combined = (existing?.quantity || 0) + quantity;
            if (combined > 100) {
                itemQuantity?.setCustomValidity("The total quantity for one item size cannot exceed 100.");
                itemQuantity?.reportValidity();
                itemQuantity?.setCustomValidity("");
                return;
            }
            selectedItems.set(sizeId, { ...match, quantity: combined });
            if (itemQuantity) itemQuantity.value = "1";
            renderSelectedItems();
        });

        form.querySelectorAll('input[name="booking_type"]').forEach((input) => input.addEventListener("change", syncService));
        form.querySelectorAll('input[name="is_summer_school"], input[name="is_study_abroad"]').forEach((input) => input.addEventListener("change", renderSummary));
        storageBoxes?.addEventListener("input", renderSummary);
        advantageBoxes?.addEventListener("input", renderSummary);

        syncSizes();
        renderSelectedItems();
        syncService();
    });
})();
