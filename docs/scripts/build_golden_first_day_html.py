#!/usr/bin/env python3
from __future__ import annotations

import json
from collections import defaultdict
from datetime import datetime
from html import escape
from pathlib import Path


ROOT = Path(__file__).resolve().parents[2]
SCENARIO_JSON = ROOT / "docs" / "generated" / "golden-first-day-scenario.json"
OUTPUT_HTML = ROOT / "docs" / "html" / "golden-first-day-scenario.html"


STEP_IMAGES = {
    1: [
        (
            "../assets/scenario-first-day/dashboard/admin-restaurants.png",
            "Platform admin session after login with restaurant management available.",
        ),
    ],
    2: [
        (
            "../assets/scenario-first-day/dashboard/admin-restaurants.png",
            "Restaurants page after Harbor Ember Kitchen was added.",
        ),
    ],
    3: [
        (
            "../assets/scenario-first-day/dashboard/admin-branches.png",
            "Branches page showing the three newly created operating branches.",
        ),
    ],
    4: [
        (
            "../assets/scenario-first-day/dashboard/admin-users.png",
            "Users page after provisioning owner, stakeholder, and branch staff.",
        ),
        (
            "../assets/scenario-first-day/dashboard/admin-roles.png",
            "Roles and permissions page used to control access across the platform.",
        ),
    ],
    5: [
        (
            "../assets/scenario-first-day/dashboard/owner-products.png",
            "Owner-scoped dashboard session restricted to Harbor Ember Kitchen.",
        ),
    ],
    6: [
        (
            "../assets/scenario-first-day/dashboard/owner-products.png",
            "Products page with the launch catalog ready to sell.",
        ),
        (
            "../assets/scenario-first-day/dashboard/owner-menus.png",
            "Menus page with branch-ready menu structure.",
        ),
        (
            "../assets/scenario-first-day/dashboard/owner-tables.png",
            "Tables page with the dine-in floor map and table inventory.",
        ),
        (
            "../assets/scenario-first-day/dashboard/owner-employees.png",
            "Employees page with operational team records linked to branches.",
        ),
    ],
    7: [
        (
            "../assets/scenario-first-day/flutter/customer-home.png",
            "Customer home showing the new restaurant, remembered orders, and rewards.",
        ),
    ],
    8: [
        (
            "../assets/scenario-first-day/flutter/waiter-floor.png",
            "Waiter floor board before and during table service.",
        ),
        (
            "../assets/scenario-first-day/flutter/waiter-order-composer.png",
            "Waiter order composer for T1 Garden with images, notes, and modifiers.",
        ),
    ],
    9: [
        (
            "../assets/scenario-first-day/flutter/waiter-order-composer.png",
            "Open order composer used to add dessert and send the ticket to KDS.",
        ),
    ],
    10: [
        (
            "../assets/scenario-first-day/flutter/waiter-order-composer.png",
            "Same waiter order surface used for quantity reduction and refund handling.",
        ),
    ],
    11: [
        (
            "../assets/scenario-first-day/flutter/kitchen-board.png",
            "Kitchen display with minimal, high-speed lane progression.",
        ),
    ],
    12: [
        (
            "../assets/scenario-first-day/flutter/cashier-settlement.png",
            "Cashier settlement workspace for final payment collection.",
        ),
    ],
    13: [
        (
            "../assets/scenario-first-day/flutter/cashier-settlement.png",
            "Cashier view reused for the drinks-only visit that closed in cash.",
        ),
    ],
    14: [
        (
            "../assets/scenario-first-day/flutter/waiter-floor.png",
            "Floor board supporting table movement from T3 Family to T4 Lounge.",
        ),
        (
            "../assets/scenario-first-day/flutter/kitchen-board.png",
            "Kitchen continued service after the table move without losing ticket continuity.",
        ),
    ],
    15: [
        (
            "../assets/scenario-first-day/dashboard/owner-orders.png",
            "Owner orders view showing paid activity across more than one branch.",
        ),
    ],
    16: [
        (
            "../assets/scenario-first-day/flutter/customer-home.png",
            "Customer home after payment with loyalty and visit memory visible.",
        ),
        (
            "../assets/scenario-first-day/flutter/customer-restaurant-detail.png",
            "Restaurant detail still linked to the same branches and menu visibility.",
        ),
    ],
    17: [
        (
            "../assets/scenario-first-day/dashboard/owner-dashboard-end-of-day.png",
            "End-of-day owner dashboard with revenue, branch ranking, payment mix, and stock alerts.",
        ),
        (
            "../assets/scenario-first-day/dashboard/owner-order-244-dialog.png",
            "Detailed web dashboard order evidence for Mona Sami's paid visit.",
        ),
    ],
}


STEP_CLICKS = {
    1: [
        "Open the platform dashboard login page.",
        "Enter `admin@restaurant-suite.com` and `password`.",
        "Click `Sign in`.",
    ],
    2: [
        "Open the left navigation and click `Restaurants`.",
        "Click `Add Restaurant`.",
        "Enter `Harbor Ember Kitchen`.",
        "Set venue type to `restaurant`.",
        "Click `Save`.",
    ],
    3: [
        "Click `Branches` in the platform navigation.",
        "Click `Add Branch` three times.",
        "Save `Downtown Flagship`, `New Cairo Studio`, and `Maadi Terrace`.",
    ],
    4: [
        "Click `Users` and choose `Add User`.",
        "Create the owner and stakeholder accounts.",
        "Create the supervisor, waiter, cashier, and kitchen accounts for each branch.",
        "Open `Roles & Permissions` to verify access boundaries.",
    ],
    5: [
        "Sign out of the platform admin session.",
        "Enter owner credentials for `Rana Soliman`.",
        "Click `Sign in` to switch to restaurant-scoped ownership.",
    ],
    6: [
        "Open `Categories` and create the launch sections.",
        "Open `Products` and save the sellable meals, drinks, and dessert items.",
        "Open `Menus` and attach products to the correct branches.",
        "Open `Tables` and define the dine-in floor map.",
        "Open `Inventory` and save stock thresholds.",
        "Open `Employees` and confirm team records are attached to branches.",
    ],
    7: [
        "Open the customer app.",
        "Enter a new phone number for Mona Sami.",
        "Finish login and land on the home marketplace.",
    ],
    8: [
        "Open the waiter app and tap `T1 Garden`.",
        "Attach Mona Sami to the table using name and phone.",
        "Add two Cedar Chicken Bowls, two Spanish Lattes, and one Citrus Sparkling Cooler.",
        "Apply modifiers and notes for taste and service changes.",
        "Tap to save the dine-in order.",
    ],
    9: [
        "Stay on the same waiter order screen.",
        "Add one Sea Salt Brownie.",
        "Click `Send to kitchen`.",
    ],
    10: [
        "Use the order adjustment controls on the active ticket.",
        "Reduce the latte quantity to one.",
        "Refund the brownie with the return note.",
    ],
    11: [
        "Open the kitchen KDS.",
        "Tap the full ticket to move queued items into preparing.",
        "Tap the ticket again to move items into ready.",
    ],
    12: [
        "Open the cashier queue.",
        "Select Mona Sami's ready order.",
        "Confirm the total and submit one card payment.",
    ],
    13: [
        "Create a second dine-in drinks-only order in T2 Window.",
        "Send it through kitchen readiness.",
        "Collect payment in cash at cashier.",
    ],
    14: [
        "Move the family table from `T3 Family` to `T4 Lounge`.",
        "Adjust one drink line after the guests changed seats.",
        "Finish kitchen production and settle with split payment.",
    ],
    15: [
        "Run one paid order in New Cairo Studio.",
        "Run one paid order in Maadi Terrace.",
        "Leave both orders completed so branch comparison becomes visible to the owner.",
    ],
    16: [
        "Open the customer app again after payment.",
        "Review remembered visits, restaurant name, branch name, and rewards.",
    ],
    17: [
        "Open the owner dashboard at close of day.",
        "Review revenue, order volume, payment mix, branch ranking, recent orders, and low-stock alerts.",
        "Open the detailed order modal for the flagship paid visit.",
    ],
}


def load_scenario() -> dict:
    return json.loads(SCENARIO_JSON.read_text(encoding="utf-8"))


def money(value: float | int) -> str:
    return f"EGP {value:,.0f}" if float(value).is_integer() else f"EGP {value:,.1f}"


def render_table(headers: list[str], rows: list[list[str]]) -> str:
    head = "".join(f"<th>{escape(header)}</th>" for header in headers)
    body_rows = []
    for row in rows:
        body_rows.append("<tr>" + "".join(f"<td>{cell}</td>" for cell in row) + "</tr>")
    return "<table><thead><tr>" + head + "</tr></thead><tbody>" + "".join(body_rows) + "</tbody></table>"


def html_for_images(step_number: int) -> str:
    images = STEP_IMAGES.get(step_number, [])
    if not images:
        return ""

    blocks = []
    for path, caption in images:
        blocks.append(
            "<div><img class=\"shot\" src=\"{src}\" alt=\"Step {step} screenshot\"><div class=\"caption\">{caption}</div></div>".format(
                src=escape(path),
                step=step_number,
                caption=escape(caption),
            )
        )

    grid_class = "grid two" if len(images) > 1 else "grid"
    return f"<div class=\"{grid_class}\">{''.join(blocks)}</div>"


def html_for_api(step: dict) -> str:
    api = step.get("api")
    extras = []
    if api:
      status = api.get("status")
      method = escape(str(api.get("method", "")))
      uri = escape(str(api.get("uri", "")))
      extras.append(
          f"<div class=\"pill-row\"><span class=\"pill\">API {method}</span><span class=\"pill\">{uri}</span><span class=\"pill\">HTTP {escape(str(status))}</span></div>"
      )

    detail_parts = []
    for key in ("order_id", "source_order_id", "target_order_id", "changed_item_id", "refunded_item_id"):
        value = step.get(key)
        if value is not None:
            detail_parts.append(f"{escape(key.replace('_', ' ').title())}: <strong>{escape(str(value))}</strong>")

    if detail_parts:
        extras.append(
            "<p class=\"caption\">" + " | ".join(detail_parts) + "</p>"
        )

    return "".join(extras)


def build_html(data: dict) -> str:
    generated_at = datetime.fromisoformat(data["generated_at"].replace("Z", "+00:00"))
    summary = data["steps"][-1]["api"]["body"]

    branch_rows = [
        [
            escape(str(branch["id"])),
            escape(branch["name"]),
            escape(branch["location"]),
        ]
        for branch in data["branches"]
    ]

    staff_rows = []
    staff_by_branch = defaultdict(list)
    for member in data["accounts"]["staff"]:
        staff_by_branch[member["branch_name"]].append(member)
    for branch_name, members in staff_by_branch.items():
        for member in members:
            types = ", ".join(member["types"]) if member["types"] else member["role"]
            staff_rows.append(
                [
                    escape(branch_name),
                    escape(member["name"]),
                    escape(types),
                    f"<code>{escape(member['email'])}</code>",
                ]
            )

    product_rows = []
    for product in data["products"][:9]:
        product_rows.append(
            [
                escape(product["branch_name"]),
                escape(product["category_name"]),
                escape(product["name"]),
                escape(money(product["price"])),
                escape(str(product["stock"])),
                escape(str(product["min_stock"])),
            ]
        )

    outcome_rows = [
        ["Revenue", money(summary["total_sales"])],
        ["Paid orders", str(summary["orders_count"])],
        ["Average ticket", money(summary["avg_order_value"])],
        ["Branches live", str(summary["branch_count"])],
        ["Products live", str(summary["product_count"])],
        ["Employees active", str(summary["employee_count"])],
        ["Loyalty members", str(summary["loyalty_members"])],
    ]

    payment_rows = [
        [escape(entry["method"].title()), escape(money(entry["total"]))]
        for entry in summary["payment_mix"]
    ]

    branch_performance_rows = [
        [
            escape(branch["name"]),
            escape(branch["location"]),
            escape(money(branch["sales"])),
            escape(str(branch["orders_count"])),
        ]
        for branch in summary["branch_performance"]
    ]

    top_product_rows = [
        [
            escape(entry["name"]),
            escape(str(entry["quantity"])),
        ]
        for entry in summary["top_products"]
    ]

    stock_rows = [
        [
            escape(item["name"]),
            escape(str(item["stock"])),
            escape(item["unit"]),
        ]
        for item in summary["low_stock_items"]
    ]

    step_cards = []
    for step in data["steps"]:
        click_items = "".join(
            f"<li>{escape(item)}</li>" for item in STEP_CLICKS.get(step["step"], [])
        )
        step_cards.append(
            f"""
    <div class="step card">
      <h3>Step {step['step']}. {escape(step['surface'])} - {escape(step['page'])}</h3>
      <div class="pill-row">
        <span class="pill">{escape(step['actor_role'])}</span>
        <span class="pill">{escape(step['actor_name'])}</span>
      </div>
      <p><strong>Action:</strong> {escape(step['action'])}</p>
      <p><strong>Outcome:</strong> {escape(step['result'])}</p>
      <p><strong>Clicks and operator actions</strong></p>
      <ul>{click_items}</ul>
      {html_for_api(step)}
      {html_for_images(step['step'])}
    </div>
"""
        )

    return f"""<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Golden First Day Scenario</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="layout">
    <div class="hero">
      <h1>Golden First Day Scenario</h1>
      <p>Real first-day sanity run for a newly signed restaurant using the full Restaurant Suite flow from platform admin setup through end-of-day owner review.</p>
      <div class="pill-row">
        <span class="pill">{escape(data['restaurant']['name'])}</span>
        <span class="pill">Generated {escape(generated_at.strftime('%Y-%m-%d %H:%M UTC'))}</span>
        <span class="pill">{escape(data['environment']['db_connection'])} scenario database</span>
      </div>
    </div>

    <div class="note">
      The operational scenario itself was executed against the live Laravel API and scenario database. Web dashboard screenshots are live captures from that run. Flutter screenshots are scenario-matched captures from the current app UI so the step-by-step report shows the exact surfaces used by customer, waiter, cashier, kitchen, and owner roles.
    </div>

    <h2 class="section-title">Scenario Setup</h2>
    <div class="grid two">
      <div class="card">
        <h3>Restaurant Created</h3>
        <p><strong>{escape(data['restaurant']['name'])}</strong> was created as a {escape(data['restaurant']['kind'])} and launched with three operating branches.</p>
        {render_table(["Branch ID", "Branch", "Location"], branch_rows)}
      </div>
      <div class="card">
        <h3>Primary Accounts</h3>
        <table>
          <thead>
            <tr><th>Role</th><th>Name</th><th>Email</th></tr>
          </thead>
          <tbody>
            <tr><td>Owner</td><td>{escape(data['accounts']['owner']['name'])}</td><td><code>{escape(data['accounts']['owner']['email'])}</code></td></tr>
            <tr><td>Stakeholder</td><td>{escape(data['accounts']['stakeholder']['name'])}</td><td><code>{escape(data['accounts']['stakeholder']['email'])}</code></td></tr>
            <tr><td>Admin</td><td>Restaurant Suite Admin</td><td><code>admin@restaurant-suite.com</code></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <h3>Operational Staff By Branch</h3>
      {render_table(["Branch", "Employee", "Role / Type", "Login"], staff_rows)}
    </div>

    <div class="card">
      <h3>Launch Catalog Snapshot</h3>
      <p>The owner seeded the first-day menu, floor map, and stock thresholds before service opened. The table below shows the first nine sellable items created across the three branches.</p>
      {render_table(["Branch", "Category", "Product", "Price", "Stock", "Min stock"], product_rows)}
    </div>

    <h2 class="section-title">Step-By-Step Timeline</h2>
    {''.join(step_cards)}

    <h2 class="section-title">End Of Day Outcome</h2>
    <div class="grid two">
      <div class="card">
        <h3>Headline KPIs</h3>
        {render_table(["Metric", "Value"], [[escape(a), escape(b)] for a, b in outcome_rows])}
      </div>
      <div class="card">
        <h3>Payment Mix</h3>
        {render_table(["Method", "Total"], payment_rows)}
      </div>
      <div class="card">
        <h3>Branch Performance</h3>
        {render_table(["Branch", "Location", "Sales", "Orders"], branch_performance_rows)}
      </div>
      <div class="card">
        <h3>Top Products</h3>
        {render_table(["Product", "Quantity"], top_product_rows)}
      </div>
      <div class="card">
        <h3>Low Stock Alerts</h3>
        {render_table(["Item", "Stock", "Unit"], stock_rows)}
      </div>
      <div class="card">
        <h3>Key Validation Points</h3>
        <ul>
          <li>Platform admin successfully created a new restaurant, three branches, and all staff accounts.</li>
          <li>Owner completed catalog, menu, table, inventory, and employee setup before service.</li>
          <li>Waiter handled dine-in ordering, add-ons, quantity changes, refund handling, and table movement.</li>
          <li>Kitchen processed KDS state changes from queued to ready.</li>
          <li>Cashier completed both single-tender and mixed-tender payment flows.</li>
          <li>Customer history and loyalty were updated after payment.</li>
          <li>Owner dashboard reflected same-day revenue, branch comparison, payment mix, and stock alerts.</li>
        </ul>
      </div>
    </div>
  </div>
</body>
</html>
"""


def main() -> int:
    data = load_scenario()
    OUTPUT_HTML.write_text(build_html(data), encoding="utf-8")
    print(f"Wrote {OUTPUT_HTML}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
