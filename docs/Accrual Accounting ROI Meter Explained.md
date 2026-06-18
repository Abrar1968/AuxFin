In accrual accounting, an **ROI Meter** tracks the efficiency of an investment by recognizing revenue when it is earned and expenses when they are incurred, rather than when cash actually changes hands. This provides a much more accurate "speedometer" of performance for long-term projects.

### \---

**1. The Core Logic: Accrual vs. Cash ROI**

In cash accounting, a large upfront payment makes your ROI look like a "crash" (negative) in month one. In **accrual accounting**, you spread that cost (depreciation/amortization) over the life of the asset, showing a more stable and realistic ROI meter.

**The Accrual ROI Formula:**

$$ROI = \\left( \\frac{\\text{Earned Revenue} - \\text{Incurred Expenses}}{\\text{Total Allocated Investment}} \\right) \\times 100$$

### \---

**2. Detailed Math Example: The "SaaS Server Upgrade"**

Imagine you spend **120,000 BDT** on a high-performance search infrastructure (like a Meilisearch/Typesense cluster) intended to last **12 months**.

#### **Step A: Calculate Monthly Incurred Expense**

Instead of counting the full 120,000 BDT in January, you accrue the expense monthly:

$$\\text{Monthly Depreciation} = \\frac{120,000 \\text{ BDT}}{12 \\text{ months}} = 10,000 \\text{ BDT/month}$$

#### **Step B: Monthly Booked / Accrual Revenue**

Suppose the upgrade allows you to handle more API calls and you issue **30,000 BDT** in invoices for the first month. In accrual-accounting theory, those unpaid invoices can be tracked as booked revenue and Accounts Receivable.

AuxFin's locked revenue-recognition rule is stricter for recognized revenue: unpaid invoices must **not** be counted as recognized revenue until `invoices.payment_completed_at` is set by admin cash confirmation.

For AuxFin dashboards:

* **Booked Revenue:** invoice total, whether paid or unpaid.
* **Recognized Revenue:** invoice total where `payment_completed_at IS NOT NULL`.
* **Accounts Receivable:** booked revenue minus recognized revenue.

#### **Step C: The ROI Calculation**

* **Net Accrual Profit:** $30,000 \\text{ (Booked Revenue)} - 10,000 \\text{ (Accrued Expense)} = 20,000 \\text{ BDT}$
* **AuxFin Recognized Profit:** use only the paid/cash-confirmed portion of the 30,000 BDT invoice total.
* **Current Investment Base:** $120,000 \\text{ BDT}$

$$ROI = \\left( \\frac{20,000}{120,000} \\right) \\times 100 = 16.67\\%$$

### \---

**3. ROI Meter Components**

To build a "Complete" Meter for a dashboard, you need four data points:

|Component|Accrual Definition|Why it matters|
|-|-|-|
|**The Needle**|Current Period ROI|Shows if the project is "in the green" right now.|
|**The Threshold**|Hurdle Rate|The minimum % required to justify the cost (e.g., 10%).|
|**Accounts Receivable**|Booked but unrecognized revenue|Shows unpaid invoice value without treating it as AuxFin recognized revenue.|
|**Accrued Liabilities**|Expenses Incurred|Ensures the meter accounts for bills you *owe* but haven't paid yet.|

### \---

**4. Implementation Logic (Backend Tip)**

If you are calculating this for a software system, your SQL query or backend logic shouldn't just sum a payments table. You must:

1. **Amortize Assets:** Create a table for fixed assets and calculate a monthly depreciation\_value.
2. **Match Periods:** Ensure the created\_at date of the expense matches the service\_period of the booked revenue.
3. **Separate Revenue Buckets:** Calculate booked revenue from invoice totals, but calculate AuxFin recognized revenue only from invoices where `payment_completed_at` is not null.


