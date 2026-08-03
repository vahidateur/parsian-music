/**
 * Alpine component: invoice line-item repeater.
 *
 * Owns only form state — all presentation lives in CSS/Tailwind classes in the view.
 * Line and invoice totals are display-only; the server recomputes them on save.
 */
export default (initialItems = [], initialTax = 0) => ({
    items: [],
    tax: 0,

    init() {
        const seed = Array.isArray(initialItems) ? initialItems : [];

        this.items = seed.length
            ? seed.map((item) => ({
                title: item.title ?? '',
                description: item.description ?? '',
                quantity: Number(item.quantity ?? 1),
                unit_price: Number(item.unit_price ?? 0),
                discount: Number(item.discount ?? 0),
            }))
            : [this.blankItem()];

        this.tax = Number(initialTax) || 0;
    },

    blankItem() {
        return { title: '', description: '', quantity: 1, unit_price: 0, discount: 0 };
    },

    addItem() {
        this.items.push(this.blankItem());
    },

    removeItem(index) {
        if (this.items.length === 1) {
            this.items[0] = this.blankItem();
            return;
        }

        this.items.splice(index, 1);
    },

    lineGross(item) {
        return (Number(item.quantity) || 0) * (Number(item.unit_price) || 0);
    },

    lineTotal(item) {
        return Math.max(0, this.lineGross(item) - (Number(item.discount) || 0));
    },

    /** Gross line value — mirrors Invoice::recalculate() on the server. */
    get subtotal() {
        return this.items.reduce((sum, item) => sum + this.lineGross(item), 0);
    },

    get discountTotal() {
        return this.items.reduce((sum, item) => sum + (Number(item.discount) || 0), 0);
    },

    get total() {
        return Math.max(0, this.subtotal - this.discountTotal + (Number(this.tax) || 0));
    },

    money(value) {
        return new Intl.NumberFormat('fa-IR').format(Math.round(value));
    },
});
