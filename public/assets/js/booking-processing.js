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
        const selectedItemsNode = form.querySelector("[data-selected-items]");
        const summaryNode = form.querySelector("[data-pricing-summary]");
        const totalNode = form.querySelector("[data-estimated-total]");
        const storageBoxes = document.getElementById("additional_box_quantity");
        const advantageBoxes = document.getElementById("box_quantity");
        const selectedListNode = form.querySelector("[data-selected-list]");

        const sizeIndex = new Map();
        catalog.forEach((item) => {
            (item.sizes || []).forEach((size) => {
                sizeIndex.set(Number(size.id), { item, size });
            });
        });

        const amount = (value) => {
            const parsed = Number.parseFloat(String(value ?? "0"));
            return Number.isFinite(parsed) ? parsed : 0;
        };
        const money = (value) => new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(value);
        const bookingType = () => form.querySelector('input[name="booking_type"]:checked')?.value || "summer_storage";
        const clampQuantity = (value) => Math.min(100, Math.max(0, Number.parseInt(String(value), 10) || 0));

        /** sizeId -> quantity, in the order items were added. */
        const quantities = new Map();

        // Restore quantities after a failed submission (server re-renders with old('items')).
        (Array.isArray(config.selectedItems) ? config.selectedItems : []).forEach((requested) => {
            const sizeId = Number(requested?.size_id || 0);
            const quantity = clampQuantity(requested?.quantity);
            if (sizeIndex.has(sizeId) && quantity > 0) quantities.set(sizeId, quantity);
        });

        /** Selected items as {sizeId, item, size, quantity}, only where quantity > 0. */
        const selectedItems = () => Array.from(quantities.entries())
            .filter(([sizeId, quantity]) => quantity > 0 && sizeIndex.has(sizeId))
            .map(([sizeId, quantity]) => ({ sizeId, quantity, ...sizeIndex.get(sizeId) }));

        const itemLabel = (match) => (match.item.sizes || []).length > 1 || match.size.name !== "Standard"
            ? `${match.item.name} · ${match.size.name}`
            : match.item.name;

        const setQuantity = (sizeId, quantity) => {
            const next = clampQuantity(quantity);
            if (next > 0) {
                quantities.set(sizeId, next);
            } else {
                quantities.delete(sizeId);
            }
            renderSelectedList();
            syncItemFields();
        };

        /** The "Your items" list: one row per chosen item size with − / + and remove. */
        const renderSelectedList = () => {
            if (!selectedListNode) return;
            selectedListNode.replaceChildren();
            const lines = selectedItems();
            if (lines.length === 0) {
                const empty = document.createElement("div");
                empty.className = "booking-empty-state";
                empty.textContent = "No additional items yet. Search above to add one.";
                selectedListNode.append(empty);
                return;
            }

            lines.forEach((line) => {
                const row = document.createElement("div");
                row.className = "booking-selected-item item-selected-row";

                const copy = document.createElement("div");
                const title = document.createElement("strong");
                const detail = document.createElement("small");
                title.textContent = itemLabel(line);
                detail.textContent = `${money(amount(line.size.unit_price))} each`;
                copy.append(title, detail);

                const controls = document.createElement("div");
                controls.className = "item-selected-controls";

                const stepper = document.createElement("div");
                stepper.className = "quantity-stepper quantity-stepper-sm";
                const minus = document.createElement("button");
                minus.type = "button";
                minus.setAttribute("aria-label", `Remove one ${itemLabel(line)}`);
                minus.innerHTML = '<span aria-hidden="true">−</span>';
                minus.addEventListener("click", () => setQuantity(line.sizeId, line.quantity - 1));
                const input = document.createElement("input");
                input.className = "form-control";
                input.type = "number";
                input.min = "0";
                input.max = "100";
                input.step = "1";
                input.inputMode = "numeric";
                input.value = String(line.quantity);
                input.setAttribute("aria-label", `${itemLabel(line)} quantity`);
                input.addEventListener("change", () => setQuantity(line.sizeId, input.value));
                const plus = document.createElement("button");
                plus.type = "button";
                plus.setAttribute("aria-label", `Add one ${itemLabel(line)}`);
                plus.innerHTML = '<span aria-hidden="true">+</span>';
                plus.disabled = line.quantity >= 100;
                plus.addEventListener("click", () => setQuantity(line.sizeId, line.quantity + 1));
                stepper.append(minus, input, plus);

                const total = document.createElement("strong");
                total.className = "item-selected-total";
                total.textContent = money(amount(line.size.unit_price) * line.quantity);

                const remove = document.createElement("button");
                remove.type = "button";
                remove.className = "item-remove";
                remove.setAttribute("aria-label", `Remove ${itemLabel(line)}`);
                remove.innerHTML = '<span aria-hidden="true">&times;</span>';
                remove.addEventListener("click", () => setQuantity(line.sizeId, 0));

                controls.append(stepper, total, remove);
                row.append(copy, controls);
                selectedListNode.append(row);
            });
        };

        // Searchable item list: type to filter, or focus the empty box to browse everything.
        const searchInput = form.querySelector("#item_search");
        const resultsNode = form.querySelector("#item_search_results");
        if (searchInput && resultsNode) {
            const searchable = Array.from(sizeIndex.entries()).map(([sizeId, match]) => ({
                sizeId,
                match,
                text: `${match.item.name} ${match.size.name} ${match.size.dimensions || ""}`.toLowerCase(),
            }));
            let activeIndex = -1;

            const closeResults = () => {
                resultsNode.hidden = true;
                searchInput.setAttribute("aria-expanded", "false");
                searchInput.removeAttribute("aria-activedescendant");
                activeIndex = -1;
            };

            const highlight = (index) => {
                const options = Array.from(resultsNode.querySelectorAll('[role="option"]'));
                if (options.length === 0) return;
                activeIndex = (index + options.length) % options.length;
                options.forEach((option, position) => option.classList.toggle("is-active", position === activeIndex));
                const active = options[activeIndex];
                searchInput.setAttribute("aria-activedescendant", active.id);
                active.scrollIntoView({ block: "nearest" });
            };

            const addFromResult = (sizeId) => {
                setQuantity(sizeId, (quantities.get(sizeId) || 0) + 1);
                const match = sizeIndex.get(sizeId);
                if (match && window.epicToast) window.epicToast("success", `${itemLabel(match)} added.`, { timeout: 1800 });
                renderResults();
            };

            const renderResults = () => {
                const terms = searchInput.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
                const matches = searchable.filter((entry) => terms.every((term) => entry.text.includes(term)));
                resultsNode.replaceChildren();
                activeIndex = -1;

                if (matches.length === 0) {
                    const none = document.createElement("li");
                    none.className = "item-search-empty";
                    none.textContent = `No items match "${searchInput.value.trim()}". Try another word, or contact us for items not listed.`;
                    resultsNode.append(none);
                }

                matches.forEach((entry) => {
                    const option = document.createElement("li");
                    option.id = `item-option-${entry.sizeId}`;
                    option.setAttribute("role", "option");
                    option.className = "item-search-option";
                    const inCart = quantities.get(entry.sizeId) || 0;
                    option.setAttribute("aria-selected", String(inCart > 0));

                    const copy = document.createElement("span");
                    copy.className = "item-search-copy";
                    const name = document.createElement("strong");
                    name.textContent = entry.match.item.name;
                    const detail = document.createElement("small");
                    detail.textContent = `${entry.match.size.name} · ${money(amount(entry.match.size.unit_price))}${inCart > 0 ? ` · ${inCart} added` : ""}`;
                    copy.append(name, detail);

                    const add = document.createElement("span");
                    add.className = "item-search-add";
                    add.setAttribute("aria-hidden", "true");
                    add.textContent = "+ Add";

                    option.append(copy, add);
                    // mousedown keeps focus in the search box so people can add several items quickly.
                    option.addEventListener("mousedown", (event) => event.preventDefault());
                    option.addEventListener("click", () => addFromResult(entry.sizeId));
                    resultsNode.append(option);
                });

                resultsNode.hidden = false;
                searchInput.setAttribute("aria-expanded", "true");
            };

            searchInput.addEventListener("input", renderResults);
            searchInput.addEventListener("focus", renderResults);
            searchInput.addEventListener("blur", closeResults);
            searchInput.addEventListener("keydown", (event) => {
                if (event.key === "ArrowDown" || event.key === "ArrowUp") {
                    event.preventDefault();
                    if (resultsNode.hidden) renderResults();
                    highlight(activeIndex + (event.key === "ArrowDown" ? 1 : -1));
                } else if (event.key === "Enter") {
                    // Never submit the booking from the search box.
                    event.preventDefault();
                    const options = Array.from(resultsNode.querySelectorAll('[role="option"]'));
                    const target = options[activeIndex >= 0 ? activeIndex : 0];
                    if (!resultsNode.hidden && target && options.length > 0 && (activeIndex >= 0 || options.length === 1)) {
                        target.click();
                    }
                } else if (event.key === "Escape") {
                    closeResults();
                }
            });
        }

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
                const total = quantity * amount(prices.summer_advantage_box_price);
                appendSummaryLine(`Summer Advantage boxes × ${quantity}`, total);
                totalNode.textContent = money(total);
                return;
            }

            const base = amount(prices.summer_storage_base_price);
            const boxQuantity = Math.max(0, Number.parseInt(storageBoxes?.value || "0", 10) || 0);
            const boxTotal = boxQuantity * amount(prices.summer_storage_additional_box_price);
            const lines = selectedItems();
            const itemsTotal = lines.reduce((total, line) => total + (amount(line.size.unit_price) * line.quantity), 0);
            const summerSchool = form.querySelector('input[name="is_summer_school"]:checked')?.value === "1";
            const studyAbroad = form.querySelector('input[name="is_study_abroad"]:checked')?.value === "1";
            const summerSchoolFee = summerSchool ? amount(prices.summer_school_fee) : 0;
            const serviceSubtotal = base + boxTotal + itemsTotal + summerSchoolFee;
            const studyAbroadCharge = studyAbroad ? serviceSubtotal : 0;

            appendSummaryLine("Summer Storage base package", base);
            if (boxQuantity > 0) appendSummaryLine(`Additional boxes × ${boxQuantity}`, boxTotal);
            lines.forEach((line) => appendSummaryLine(`${line.item.name} (${line.size.name}) × ${line.quantity}`, amount(line.size.unit_price) * line.quantity));
            if (summerSchoolFee > 0) appendSummaryLine("Summer school fee", summerSchoolFee);
            if (studyAbroadCharge > 0) appendSummaryLine("Study-abroad second semester", studyAbroadCharge);
            totalNode.textContent = money(serviceSubtotal + studyAbroadCharge);
        };

        /** Mirrors picker quantities into the items[n][size_id|quantity] fields the server expects. */
        const syncItemFields = () => {
            if (selectedItemsNode) {
                selectedItemsNode.replaceChildren();
                if (bookingType() === "summer_storage") {
                    selectedItems().forEach((line, index) => {
                        [["size_id", line.sizeId], ["quantity", line.quantity]].forEach(([key, value]) => {
                            const input = document.createElement("input");
                            input.type = "hidden";
                            input.name = `items[${index}][${key}]`;
                            input.value = String(value);
                            selectedItemsNode.append(input);
                        });
                    });
                }
            }
            renderSummary();
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
            syncItemFields();
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
            input.value = String(Math.min(maximum, Math.max(minimum, safeCurrent + direction)));
            input.dispatchEvent(new Event("input", { bubbles: true }));
            input.dispatchEvent(new Event("change", { bubbles: true }));
        });

        form.querySelectorAll('input[name="booking_type"]').forEach((input) => input.addEventListener("change", syncService));
        form.querySelectorAll('input[name="is_summer_school"], input[name="is_study_abroad"]').forEach((input) => input.addEventListener("change", renderSummary));
        storageBoxes?.addEventListener("input", renderSummary);
        advantageBoxes?.addEventListener("input", renderSummary);

        // Pickup time slots: buttons for the chosen date, using the same rules the server checks.
        const slotPicker = form.querySelector("[data-slot-picker]");
        const dateInput = form.querySelector("[data-slot-date]");
        const timeInput = slotPicker?.querySelector('input[name="pickup_time"]');
        const slotButtons = slotPicker?.querySelector("[data-slot-buttons]");
        const slotGrid = slotPicker?.querySelector("[data-slot-grid]");
        const slotMessage = slotPicker?.querySelector("[data-slot-message]");
        const fallback = slotPicker?.querySelector("[data-slot-fallback]");

        if (slotPicker && dateInput && timeInput && slotButtons && slotGrid && slotMessage && fallback && config.availabilityUrl && window.fetch) {
            // The native time field stays in the form (hidden) and still carries the value.
            fallback.hidden = true;
            slotButtons.hidden = false;
            let requestId = 0;

            const renderSlots = (slots) => {
                slotGrid.replaceChildren();
                slots.forEach((slot) => {
                    const button = document.createElement("button");
                    button.type = "button";
                    button.className = "slot-button";
                    button.textContent = slot.label;
                    button.disabled = !slot.available;
                    button.setAttribute("aria-pressed", String(timeInput.value === slot.time));
                    if (!slot.available) button.title = "Not available";
                    button.addEventListener("click", () => {
                        const alreadySelected = timeInput.value === slot.time;
                        timeInput.value = alreadySelected ? "" : slot.time;
                        slotGrid.querySelectorAll(".slot-button").forEach((other) => other.setAttribute("aria-pressed", "false"));
                        button.setAttribute("aria-pressed", String(!alreadySelected));
                    });
                    slotGrid.append(button);
                });
            };

            const loadSlots = async () => {
                const date = dateInput.value;
                const current = ++requestId;
                slotGrid.replaceChildren();
                if (!date) {
                    timeInput.value = "";
                    slotMessage.textContent = "Choose a pickup date to see available times.";
                    return;
                }
                slotMessage.textContent = "Checking available times…";
                try {
                    const url = new URL(config.availabilityUrl, window.location.href);
                    url.searchParams.set("date", date);
                    const response = await fetch(url.toString(), { credentials: "same-origin", headers: { Accept: "application/json" } });
                    const payload = await response.json();
                    if (current !== requestId) return;
                    const slots = Array.isArray(payload.slots) ? payload.slots : [];
                    if (!slots.some((slot) => slot.time === timeInput.value && slot.available)) timeInput.value = "";
                    renderSlots(slots);
                    slotMessage.textContent = payload.available ? "Tap a time to select it. Tap it again to clear it." : (payload.message || "No times are available on this date.");
                } catch {
                    if (current !== requestId) return;
                    // Network problem: fall back to the plain time field so booking still works.
                    fallback.hidden = false;
                    slotButtons.hidden = true;
                }
            };

            dateInput.addEventListener("change", loadSlots);
            if (dateInput.value) loadSlots();
        }

        renderSelectedList();
        syncService();
    });
})();
