<!doctype html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>إدارة مطعم {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root {
            --cyan: #08dcf4;
            --cyan-soft: rgba(8, 220, 244, .12);
            --purple: #762cff;
            --green: #25df87;
            --yellow: #ffd43b;
            --red: #ff6274;
            --bg: #05030d;
            --panel: rgba(17, 20, 29, .92);
            --panel-2: rgba(10, 13, 19, .9);
            --border: rgba(139, 160, 179, .2);
            --muted: #9ba7b4
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at 83% 8%, rgba(0, 222, 244, .12), transparent 31%),
                radial-gradient(circle at 8% 18%, rgba(118, 44, 255, .13), transparent 29%),
                linear-gradient(135deg, #080313, #02080a 65%, #071118);
            color: #f7f9fb;
            font-family: "Cairo", Arial, sans-serif
        }

        button,
        input,
        select {
            font: inherit
        }

        .restaurant-page {
            width: auto;
            max-width: none;
            margin: 0 245px 0 0;
            padding: 26px clamp(18px, 2.2vw, 36px) 52px;
            overflow: hidden
        }

        .glass {
            background: linear-gradient(135deg, rgba(25, 23, 39, .91), rgba(11, 25, 28, .9));
            border: 1px solid var(--border);
            box-shadow: 0 20px 70px rgba(0, 0, 0, .25);
            backdrop-filter: blur(15px)
        }

        .hero {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            padding: 24px 28px;
            margin-bottom: 18px
        }

        .hero:before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(100deg, rgba(118, 44, 255, .12), transparent 43%, rgba(8, 220, 244, .1));
            pointer-events: none
        }

        .hero-top,
        .hero-actions,
        .section-head,
        .live-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            position: relative
        }

        .eyebrow {
            color: var(--cyan);
            font-weight: 800;
            font-size: 12px
        }

        .hero h1 {
            font-size: clamp(24px, 2.35vw, 36px);
            line-height: 1.35;
            margin: 6px 0
        }

        .hero p {
            color: var(--muted);
            margin: 0;
            max-width: 750px;
            font-size: 14px;
            font-weight: 600
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 45px;
            padding: 9px 18px;
            border-radius: 14px;
            border: 1px solid rgba(8, 220, 244, .32);
            background: rgba(8, 220, 244, .08);
            color: var(--cyan);
            text-decoration: none;
            font-weight: 800;
            cursor: pointer;
            transition: .2s
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(8, 220, 244, .18)
        }

        .btn-primary {
            background: linear-gradient(135deg, #0cd9ec, #22bff1);
            color: #021014;
            border: 0
        }

        .btn-danger {
            color: #ff9ba6;
            border-color: rgba(255, 98, 116, .35);
            background: rgba(255, 98, 116, .09)
        }

        .availability-form {
            margin: 0
        }

        .availability-toggle {
            min-width: 225px
        }

        .availability-toggle.is-open {
            color: #74f2ae;
            border-color: rgba(37, 223, 135, .48);
            background: rgba(37, 223, 135, .12);
            box-shadow: 0 0 24px rgba(37, 223, 135, .09)
        }

        .availability-toggle.is-closed {
            color: #ff9ba6;
            border-color: rgba(255, 98, 116, .5);
            background: rgba(255, 98, 116, .12);
            box-shadow: 0 0 24px rgba(255, 98, 116, .1)
        }

        .availability-toggle .availability-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 12px currentColor
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 13px;
            margin-bottom: 18px
        }

        .stat {
            position: relative;
            overflow: hidden;
            border-radius: 19px;
            padding: 17px 19px;
            min-height: 112px
        }

        .stat:after {
            content: "";
            position: absolute;
            width: 85px;
            height: 85px;
            border-radius: 50%;
            background: var(--accent);
            filter: blur(50px);
            opacity: .27;
            left: -8px;
            bottom: -20px
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 13px;
            display: grid;
            place-items: center;
            background: color-mix(in srgb, var(--accent) 14%, transparent);
            color: var(--accent);
            font-size: 21px
        }

        .stat-label {
            color: #c3cad2;
            font-size: 14px;
            font-weight: 700
        }

        .stat strong {
            display: block;
            color: var(--accent);
            font-size: 30px;
            margin-top: 5px;
            line-height: 1
        }

        .section {
            border-radius: 23px;
            padding: 21px;
            margin-bottom: 18px
        }

        .section-head {
            margin-bottom: 16px
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 11px
        }

        .section-icon {
            width: 43px;
            height: 43px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(8, 220, 244, .32);
            background: var(--cyan-soft);
            color: var(--cyan);
            font-size: 22px
        }

        .section h2 {
            font-size: 21px;
            margin: 0
        }

        .section-subtitle {
            color: var(--muted);
            font-size: 12px;
            margin-top: 2px
        }

        .form-row {
            display: grid;
            grid-template-columns: minmax(180px, 1.4fr) minmax(150px, 1fr) auto;
            gap: 12px
        }

        .field {
            width: 100%;
            min-height: 49px;
            border: 1px solid var(--border);
            background: rgba(5, 8, 13, .75);
            color: #fff;
            border-radius: 14px;
            padding: 10px 15px;
            outline: 0
        }

        .field:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 0 3px rgba(8, 220, 244, .1)
        }

        .field::placeholder {
            color: #687583
        }

        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 13px;
            margin-top: 16px
        }

        .table-card {
            border: 1px solid var(--border);
            background: rgba(5, 8, 13, .72);
            border-radius: 18px;
            padding: 15px;
            text-align: center
        }

        .table-card h3 {
            margin: 3px 0
        }

        .table-card p {
            color: var(--muted);
            margin: 2px
        }

        .qr-box {
            width: 125px;
            height: 125px;
            padding: 7px;
            background: #fff;
            border-radius: 14px;
            margin: 10px auto
        }

        .qr-box img {
            width: 100%;
            height: 100%
        }

        .table-actions {
            display: flex;
            justify-content: center;
            gap: 7px;
            flex-wrap: wrap
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(3, minmax(155px, 1fr)) auto;
            gap: 10px;
            width: min(820px, 100%)
        }

        .live {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--green);
            font-size: 12px;
            font-weight: 800
        }

        .live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 13px currentColor;
            animation: pulse 1.6s infinite
        }

        @keyframes pulse {
            50% {
                opacity: .4
            }
        }

        .live-tools {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap
        }

        .sound-toggle {
            min-height: 38px;
            padding: 6px 12px;
            font-size: 11px
        }

        .sound-toggle.enabled {
            color: var(--green);
            border-color: rgba(37, 223, 135, .38);
            background: rgba(37, 223, 135, .1)
        }

        .sound-toggle.attention {
            color: var(--yellow);
            border-color: rgba(255, 212, 59, .48);
            animation: soundAttention 1s infinite alternate
        }

        @keyframes soundAttention {
            to {
                box-shadow: 0 0 20px rgba(255, 212, 59, .3)
            }
        }

        [hidden] {
            display: none !important
        }

        .order-alarm {
            position: fixed;
            z-index: 1000;
            top: 18px;
            left: 50%;
            transform: translateX(-50%);
            width: min(560px, calc(100% - 24px));
            border: 1px solid rgba(255, 212, 59, .75);
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(24, 14, 5, .98), rgba(14, 21, 25, .98));
            color: #fff;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            text-align: right;
            box-shadow: 0 18px 65px rgba(0, 0, 0, .7), 0 0 35px rgba(255, 212, 59, .3);
            cursor: pointer;
            font-family: inherit;
            animation: alarmPulse .85s infinite alternate
        }

        .order-alarm:hover {
            transform: translateX(-50%) translateY(-2px)
        }

        @keyframes alarmPulse {
            to {
                border-color: var(--red);
                box-shadow: 0 18px 65px rgba(0, 0, 0, .7), 0 0 42px rgba(255, 98, 116, .43)
            }
        }

        .alarm-icon {
            width: 52px;
            height: 52px;
            border-radius: 17px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            background: rgba(255, 212, 59, .16);
            color: var(--yellow);
            font-size: 29px
        }

        .alarm-copy {
            min-width: 0;
            flex: 1
        }

        .alarm-copy strong {
            display: block;
            font-size: 17px;
            color: var(--yellow)
        }

        .alarm-copy span {
            display: block;
            margin-top: 3px;
            color: #d8dee5;
            font-size: 12px;
            font-weight: 700
        }

        .alarm-action {
            flex: 0 0 auto;
            border-radius: 999px;
            padding: 8px 13px;
            background: var(--yellow);
            color: #171000;
            font-size: 11px;
            font-weight: 900
        }

        .orders-wrap {
            overflow: auto;
            border: 1px solid var(--border);
            border-radius: 19px;
            background: rgba(3, 6, 10, .55)
        }

        table {
            width: 100%;
            min-width: 680px;
            border-collapse: collapse
        }

        th {
            color: var(--cyan);
            font-size: 13px;
            background: rgba(8, 220, 244, .05)
        }

        th,
        td {
            text-align: right;
            padding: 16px;
            border-bottom: 1px solid rgba(139, 160, 179, .13);
            vertical-align: top
        }

        tbody tr {
            transition: .2s
        }

        tbody tr:hover {
            background: rgba(8, 220, 244, .035)
        }

        tbody tr:last-child td {
            border-bottom: 0
        }

        small {
            color: var(--muted)
        }

        .order-summary-row {
            cursor: pointer
        }

        .order-summary-row td {
            padding: 13px 15px;
            vertical-align: middle
        }

        .order-number-link {
            display: inline-block;
            color: #fff;
            font-weight: 900;
            text-decoration: none
        }

        .order-summary-row:hover .order-number-link {
            color: var(--cyan)
        }

        .order-summary-row small {
            display: block;
            margin-top: 5px;
            font-size: 10px
        }

        .order-meals {
            min-width: 250px;
            line-height: 1.75
        }

        .more-items {
            color: var(--cyan)
        }

        .order-total {
            color: #ffe29a;
            white-space: nowrap;
            font-size: 16px;
            font-weight: 900
        }

        .tag {
            display: inline-flex;
            align-items: center;
            padding: 5px 11px;
            border-radius: 30px;
            background: var(--cyan-soft);
            color: var(--cyan);
            border: 1px solid rgba(8, 220, 244, .24);
            font-size: 12px;
            font-weight: 800
        }

        .status-form {
            display: grid;
            grid-template-columns: minmax(135px, 1fr) minmax(125px, .8fr);
            gap: 8px;
            min-width: 285px
        }

        .status-field {
            display: grid;
            gap: 5px
        }

        .status-field>span {
            color: #cbd4dc;
            font-size: 10px;
            font-weight: 800
        }

        .status-form .field {
            min-height: 40px;
            margin: 0;
            padding: 6px 10px
        }

        .preparation-picker {
            display: grid;
            gap: 9px;
            min-width: 0;
            margin: 0;
            padding: 11px;
            border: 1px solid rgba(8, 220, 244, .2);
            border-radius: 16px;
            background: linear-gradient(145deg, rgba(8, 220, 244, .07), rgba(8, 13, 19, .72));
        }

        .preparation-picker legend {
            padding: 0 4px;
            color: #dce9f0;
            font-size: 11px;
            font-weight: 900;
        }

        .preparation-picker legend i {
            margin-inline-end: 4px;
            color: var(--cyan);
        }

        .preparation-options {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 7px;
        }

        .preparation-choice {
            display: grid;
            place-content: center;
            gap: 1px;
            aspect-ratio: 1;
            min-width: 0;
            padding: 3px;
            border: 1px solid rgba(139, 160, 179, .22);
            border-radius: 50%;
            background: rgba(3, 8, 13, .82);
            color: #94a5b5;
            cursor: pointer;
            transition: transform .18s ease, color .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease;
        }

        .preparation-choice b {
            font-size: 13px;
            line-height: 1;
        }

        .preparation-choice small {
            color: inherit;
            font-size: 8px;
            font-weight: 800;
            line-height: 1;
        }

        .preparation-choice:not(:disabled):hover {
            transform: translateY(-2px) scale(1.04);
            color: #effcff;
            border-color: var(--cyan);
        }

        .preparation-choice:focus-visible {
            outline: 3px solid var(--cyan);
            outline-offset: 2px;
        }

        .preparation-choice.is-selected {
            color: #06212a;
            border-color: var(--cyan);
            background: var(--cyan);
            box-shadow: 0 0 15px rgba(8, 220, 244, .32);
        }

        .preparation-choice:disabled {
            cursor: wait;
        }

        .status-form .btn {
            grid-column: 1/-1;
            min-height: 40px;
            padding: 6px 12px
        }

        .status-help {
            grid-column: 1/-1;
            color: var(--muted);
            font-size: 10px;
            line-height: 1.6
        }

        .status-form {
            grid-template-columns: minmax(0, 1fr);
            min-width: 300px;
            max-width: 390px
        }

        .status-choices {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            align-items: center
        }

        .status-choice {
            width: 62px;
            height: 62px;
            flex: 0 0 62px;
            padding: 5px;
            border: 1px solid var(--border);
            border-radius: 50%;
            background: rgba(8, 13, 19, .9);
            color: #d7e2ea;
            font-size: 10px;
            font-weight: 800;
            line-height: 1.35;
            text-align: center;
            cursor: pointer;
            transition: transform .18s, border-color .18s, box-shadow .18s
        }

        .status-choice:not(:disabled):hover {
            transform: translateY(-3px);
            border-color: var(--cyan);
            box-shadow: 0 0 18px rgba(8, 220, 244, .2)
        }

        .status-choice:focus-visible {
            outline: 3px solid var(--cyan);
            outline-offset: 3px
        }

        .status-choice.is-current {
            color: var(--cyan);
            border-color: var(--cyan);
            background: var(--cyan-soft);
            box-shadow: 0 0 14px rgba(8, 220, 244, .16)
        }

        .status-choice-preparing.is-current {
            color: var(--yellow);
            border-color: var(--yellow);
            background: rgba(255, 212, 59, .12)
        }

        .status-choice-ready.is-current,
        .status-choice-completed.is-current {
            color: var(--green);
            border-color: var(--green);
            background: rgba(37, 223, 135, .12)
        }

        .status-choice-cancelled.is-current {
            color: var(--red);
            border-color: var(--red);
            background: rgba(255, 98, 116, .12)
        }

        .status-choice:disabled:not(.is-current) {
            opacity: .37;
            cursor: not-allowed
        }

        .status-form.is-saving {
            opacity: .72
        }

        .status-form .status-field,
        .status-form .preparation-picker,
        .status-form .status-help,
        .status-form .status-save,
        .status-feedback {
            grid-column: 1/-1
        }

        .status-feedback {
            display: block;
            min-height: 18px;
            font-size: 11px;
            font-weight: 800;
            color: var(--green)
        }

        .status-feedback.is-error {
            color: var(--red)
        }

        .driver-assignment {
            display: grid;
            gap: 7px;
            margin-top: 13px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
            font-size: 12px
        }

        .driver-assignment>strong {
            color: var(--cyan)
        }

        .driver-assign-form {
            display: grid;
            gap: 8px
        }

        .driver-assign-form .field {
            min-height: 40px
        }

        .driver-assign-form .btn {
            min-height: 40px
        }

        .driver-management-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px
        }

        .driver-management-form .btn {
            grid-column: 1/-1
        }

        .driver-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 12px;
            margin-top: 16px
        }

        .driver-card {
            padding: 14px;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: rgba(5, 9, 15, .7)
        }

        .driver-card strong,
        .driver-card small {
            display: block
        }

        .driver-card small {
            overflow-wrap: anywhere
        }

        .driver-card form {
            margin-top: 10px
        }

        .driver-card .btn {
            min-height: 36px
        }

        .status-choice-out_for_delivery.is-current {
            color: var(--purple);
            border-color: var(--purple);
            background: rgba(118, 44, 255, .14)
        }

        .notice {
            border-radius: 16px;
            padding: 14px 18px;
            margin-bottom: 18px
        }

        .notice-success {
            border: 1px solid rgba(37, 223, 135, .35);
            background: rgba(37, 223, 135, .09);
            color: #73f2ae
        }

        .notice-error {
            border: 1px solid rgba(255, 98, 116, .35);
            background: rgba(255, 98, 116, .09);
            color: #ffabb5
        }

        .empty {
            grid-column: 1/-1;
            color: var(--muted);
            text-align: center;
            padding: 18px
        }

        nav[role="navigation"] {
            margin-top: 18px
        }

        @media(max-width:1250px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr)
            }
        }

        @media(max-width:900px) {
            .restaurant-page {
                width: 100%;
                margin: 0;
                padding: 18px 14px 100px
            }

            .hero {
                padding: 23px
            }

            .hero-top,
            .hero-actions,
            .section-head,
            .live-heading {
                align-items: flex-start;
                flex-direction: column
            }

            .hero-actions,
            .availability-form,
            .availability-toggle {
                width: 100%
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr)
            }

            .section {
                padding: 19px
            }

            .form-row,
            .filters {
                grid-template-columns: 1fr
            }

            .form-row .btn,
            .filters .btn {
                width: 100%
            }

            .live-tools {
                width: 100%;
                justify-content: space-between
            }

            .driver-management-form {
                grid-template-columns: 1fr
            }
        }

        @media(max-width:620px) {
            .hero {
                padding: 18px
            }

            .hero-actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                align-items: stretch;
                gap: 9px;
            }

            .hero-actions .availability-form {
                grid-column: 1 / -1;
                width: 100%;
            }

            .hero-actions .availability-toggle {
                min-width: 0;
                min-height: 52px;
                padding: 10px 14px;
                font-size: 15px;
                white-space: nowrap;
            }

            .hero-actions > .btn {
                width: 100%;
                min-width: 0;
                min-height: 52px;
                padding: 9px 8px;
                border-radius: 13px;
                gap: 6px;
                font-size: 13px;
                line-height: 1.25;
            }

            .hero-actions > .btn i {
                flex: 0 0 auto;
                font-size: 18px;
            }

            .hero-actions > .btn:last-child {
                grid-column: 1 / -1;
            }
        }

        @media(max-width:900px) {
            .orders-wrap {
                overflow: visible;
                border: 0;
                border-radius: 0;
                background: transparent
            }

            .orders-wrap table,
            .orders-wrap tbody {
                display: block;
                width: 100%;
                min-width: 0
            }

            .orders-wrap thead {
                display: none
            }

            .orders-wrap tbody tr {
                display: grid;
                grid-template-columns: minmax(0, 1fr) max-content;
                width: 100%;
                min-width: 0;
                margin-bottom: 13px;
                overflow: hidden;
                border: 1px solid var(--border);
                border-radius: 18px;
                background: rgba(7, 15, 21, .92)
            }

            .orders-wrap tbody tr:last-child {
                margin-bottom: 0
            }

            .orders-wrap tbody td {
                display: block;
                min-width: 0;
                padding: 11px 14px;
                border: 0;
                overflow-wrap: anywhere
            }

            .orders-wrap tbody td::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 4px;
                color: var(--cyan);
                font-size: 11px;
                font-weight: 800
            }

            .orders-wrap tbody td:nth-child(-n+2) {
                background: rgba(8, 220, 244, .045)
            }

            .orders-wrap tbody td:nth-child(1) {
                font-size: 15px;
                font-weight: 800
            }

            .orders-wrap tbody td:nth-child(1) small {
                display: block;
                font-size: 11px;
                font-weight: 600
            }

            .orders-wrap tbody td:nth-child(2) {
                text-align: left
            }

            .orders-wrap tbody td:nth-child(n+3) {
                grid-column: 1/-1;
                border-top: 1px solid rgba(139, 160, 179, .13)
            }

            .orders-wrap tbody td:nth-child(5) {
                color: var(--cyan);
                font-size: 19px;
                font-weight: 800
            }

            .orders-wrap tbody td[colspan] {
                grid-column: 1/-1;
                text-align: center
            }

            .orders-wrap .status-form {
                width: 100%;
                min-width: 0;
                max-width: none
            }

            .orders-wrap .status-choices {
                gap: 6px
            }

            .orders-wrap .status-choice {
                width: 56px;
                height: 56px;
                flex-basis: 56px;
                font-size: 9px
            }

            .orders-wrap .preparation-options {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }

            .orders-wrap tbody tr.order-summary-row {
                grid-template-columns: minmax(0, 1fr) max-content;
                cursor: pointer
            }

            .orders-wrap tbody tr.order-summary-row td:nth-child(1),
            .orders-wrap tbody tr.order-summary-row td:nth-child(2) {
                grid-column: auto;
                background: rgba(8, 220, 244, .045)
            }

            .orders-wrap tbody tr.order-summary-row td:nth-child(3),
            .orders-wrap tbody tr.order-summary-row td:nth-child(4) {
                grid-column: auto;
                border-top: 1px solid rgba(139, 160, 179, .13)
            }

            .orders-wrap tbody tr.order-summary-row td::before {
                font-size: 10px
            }
        }

        @media(max-width:600px) {
            .order-alarm {
                top: 9px;
                padding: 13px;
                gap: 10px;
                border-radius: 18px
            }

            .alarm-icon {
                width: 44px;
                height: 44px;
                border-radius: 14px;
                font-size: 24px
            }

            .alarm-copy strong {
                font-size: 14px
            }

            .alarm-copy span {
                font-size: 10px
            }

            .alarm-action {
                padding: 7px 9px;
                font-size: 9px
            }
        }

        @media(max-width:520px) {
            .restaurant-page {
                padding-inline: 10px
            }

            .hero {
                border-radius: 21px;
                padding: 19px
            }

            .hero h1 {
                font-size: 25px
            }

            .stats-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 7px
            }

            .stat {
                min-height: 82px;
                padding: 10px
            }

            .stat strong {
                margin-top: 3px;
                font-size: 22px
            }

            .stat-icon {
                width: 30px;
                height: 30px;
                border-radius:10px;
                font-size:16px
            }

            .stat-label {
                font-size: 10px
            }

            .stat:last-child {
                grid-column: span 2
            }

            .section {
                border-radius: 21px;
                padding: 15px
            }

            .section h2 {
                font-size: 20px
            }

            .tables-grid {
                grid-template-columns: 1fr
            }
        }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    <button type="button" class="order-alarm" id="restaurant-order-alarm" hidden
        aria-label="فتح الطلب الجديد وإيقاف صوت التنبيه">
        <span class="alarm-icon"><i class="ti ti-bell-ringing" aria-hidden="true"></i></span>
        <span class="alarm-copy">
            <strong>وصل طلب جديد!</strong>
            <span id="restaurant-order-alarm-message">اضغط هنا لفتح الطلب وإيقاف صوت التنبيه.</span>
        </span>
        <span class="alarm-action">فتح وإيقاف الصوت</span>
    </button>
    <main class="restaurant-page">
        <section class="hero glass">
            <div class="hero-top">
                <div>
                    <span class="eyebrow">OZMAN RESTAURANT CONTROL</span>
                    <h1>إدارة مطعم {{ $shop->name }}</h1>
                    <p>لوحة موحّدة لإدارة الطاولات، طلبات الصالة، الطلبات الأونلاين، المطبخ والكاشير.</p>
                </div>
                <div class="hero-actions">
                    @if (auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('restaurant.availability'))
                        <form class="availability-form" method="post"
                            action="{{ route('restaurant.availability', $shop) }}">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="is_accepting_orders"
                                value="{{ $shop->is_accepting_orders ? 0 : 1 }}">
                            <button
                                class="btn availability-toggle {{ $shop->is_accepting_orders ? 'is-open' : 'is-closed' }}"
                                type="submit">
                                <span class="availability-dot" aria-hidden="true"></span>
                                {{ $shop->is_accepting_orders ? 'المطعم مفتوح — إغلاق المطعم' : 'المطعم مغلق — فتح المطعم' }}
                            </button>
                        </form>
                    @else
                        <span
                            class="btn availability-toggle {{ $shop->is_accepting_orders ? 'is-open' : 'is-closed' }}">
                            <span class="availability-dot" aria-hidden="true"></span>
                            {{ $shop->is_accepting_orders ? 'المطعم مفتوح' : 'المطعم مغلق' }}
                        </span>
                    @endif
                    <a class="btn" href="{{ route('shops.show', $shop) }}"><i class="ti ti-arrow-right"></i> لوحة
                        المتجر</a>
                    @if (auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('products'))
                        <a class="btn" href="{{ route('products', ['shop_id' => $shop->id]) }}"><i
                                class="ti ti-tools-kitchen-2"></i> إدارة الوجبات</a>
                    @endif
                    @if (auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('restaurant.tables.index'))
                        <a class="btn" href="{{ route('restaurant.tables.index', $shop) }}"><i class="ti ti-qrcode"></i> طاولات المطعم</a>
                    @endif
                    @if (auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('restaurant.drivers.index'))
                        <a class="btn" href="{{ route('restaurant.drivers.index', $shop) }}"><i class="ti ti-motorbike"></i> مندوبي التوصيل</a>
                    @endif
                    <a class="btn btn-primary" href="{{ route('restaurant.menu', $shop) }}" target="_blank"><i
                            class="ti ti-external-link"></i> فتح منيو المطعم</a>
                </div>
            </div>
        </section>

        @if (session('status'))
            <div class="notice notice-success"><i class="ti ti-circle-check"></i> {{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice notice-error"><b>تعذّر تنفيذ العملية:</b>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="stats-grid">
            <article class="stat glass" style="--accent:var(--cyan)">
                <div class="stat-icon"><i class="ti ti-receipt"></i></div><span class="stat-label">طلبات
                    <span id="stat-period-label">{{ $selectedPeriodLabel }}</span></span><strong id="stat-orders">{{ $stats['orders'] }}</strong>
            </article>
            <article class="stat glass" style="--accent:var(--green)">
                <div class="stat-icon"><i class="ti ti-sparkles"></i></div><span class="stat-label">طلبات
                    جديدة</span><strong id="stat-new">{{ $stats['new'] }}</strong>
            </article>
            <article class="stat glass" style="--accent:var(--yellow)">
                <div class="stat-icon"><i class="ti ti-chef-hat"></i></div><span class="stat-label">قيد
                    التحضير</span><strong id="stat-preparing">{{ $stats['preparing'] }}</strong>
            </article>
            <article class="stat glass" style="--accent:var(--purple)">
                <div class="stat-icon"><i class="ti ti-bell-check"></i></div><span class="stat-label">جاهزة
                    للتسليم</span><strong id="stat-ready">{{ $stats['ready'] }}</strong>
            </article>
            <article class="stat glass" style="--accent:#ff9f43" title="يُحسب من الطلبات المكتملة فقط">
                <div class="stat-icon"><i class="ti ti-cash"></i></div><span class="stat-label">إجمالي مبيعات
                    المطعم</span><strong id="stat-sales-total">{{ number_format((float) $stats['sales_total'], 2) }}
                    ₪</strong>
            </article>
        </section>

        {{-- نُقلت إدارة المندوبين إلى صفحة مستقلة. --}}
        {{--
            <section class="section glass" id="restaurant-drivers">
                <div class="section-head">
                    <div class="section-title"><span class="section-icon"><i class="ti ti-motorbike"></i></span>
                        <div>
                            <h2>مندوبي التوصيل</h2>
                            <div class="section-subtitle">أضف مندوبًا، ثم اختره في طلب التوصيل. يرى المندوب الطلب في
                                صفحته ويبدأ التوصيل بعد أن يصبح جاهزًا.</div>
                        </div>
                    </div>
                    <label class="status-field"><span>رابط دخول المندوب — انسخه وأرسله له</span><input class="field"
                            readonly value="{{ route('driver.login') }}" aria-label="رابط دخول المندوب"
                            onclick="this.select()"></label>
                </div>
                <form class="driver-management-form" method="post"
                    action="{{ route('restaurant.drivers.store', $shop) }}">
                    @csrf
                    <input class="field" name="name" value="{{ old('name') }}" placeholder="اسم المندوب"
                        autocomplete="name" required maxlength="255">
                    <input class="field" name="phone" value="{{ old('phone') }}" placeholder="رقم الجوال"
                        inputmode="tel" autocomplete="tel" required maxlength="60">
                    <input class="field" name="email" value="{{ old('email') }}"
                        placeholder="البريد الإلكتروني للدخول" type="email" autocomplete="off" required
                        maxlength="255">
                    <input class="field" name="password" placeholder="كلمة مرور للمندوب (8 أحرف على الأقل)"
                        type="password" autocomplete="new-password" required minlength="8">
                    <input class="field" name="password_confirmation" placeholder="تأكيد كلمة المرور"
                        type="password" autocomplete="new-password" required minlength="8">
                    <button class="btn btn-primary" type="submit"><i class="ti ti-user-plus"></i> إضافة مندوب وربطه
                        بالمطعم</button>
                </form>
                <div class="driver-grid">
                    @forelse($drivers as $driver)
                        <article class="driver-card">
                            <strong>{{ $driver->user?->name }} <span
                                    class="tag">{{ $driver->is_active ? 'فعال' : 'متوقف' }}</span></strong>
                            <small>{{ $driver->user?->phone }} · {{ $driver->user?->email }}</small>
                            @if (auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('restaurant.drivers.toggle'))
                                <form method="post" action="{{ route('restaurant.drivers.toggle', $driver) }}">
                                    @csrf @method('patch')
                                    <input type="hidden" name="is_active" value="{{ $driver->is_active ? 0 : 1 }}">
                                    <button class="btn {{ $driver->is_active ? 'btn-danger' : '' }}"
                                        type="submit">{{ $driver->is_active ? 'إيقاف المندوب' : 'تفعيل المندوب' }}</button>
                                </form>
                            @endif
                        </article>
                    @empty
                        <p class="empty">لا يوجد مندوبون بعد.</p>
                    @endforelse
                </div>
            </section>
        --}}

        <section class="section glass">
            <div class="live-heading">
                <div class="section-title"><span class="section-icon"><i class="ti ti-chef-hat"></i></span>
                    <div>
                        <h2>شاشة المطبخ والكاشير</h2>
                        <div class="section-subtitle">طلبات الصالة والتوصيل والاستلام في مكان واحد.</div>
                    </div>
                </div>
                <div class="live-tools">
                    <button type="button" class="btn sound-toggle" id="restaurant-sound-toggle"><i
                            class="ti ti-volume"></i> تفعيل صوت الطلبات</button>
                    <span class="live" id="live-status"><i class="live-dot"></i> متصل وتحديث مباشر</span>
                </div>
            </div>
            <form class="filters" id="restaurant-orders-filter" method="get" style="margin:22px 0 16px">
                <select class="field" name="period">
                    @foreach (['today' => 'طلبات اليوم', 'week' => 'طلبات هذا الأسبوع', 'month' => 'طلبات هذا الشهر', 'all' => 'كل الطلبات'] as $key => $label)
                        <option value="{{ $key }}" @selected($selectedPeriod === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select class="field" name="type">
                    <option value="">كل أنواع الطلب</option>
                    @foreach (['dine_in' => 'طلبات الطاولات', 'delivery' => 'توصيل', 'pickup' => 'استلام'] as $key => $label)
                        <option value="{{ $key }}" @selected($selectedType === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select class="field" name="status">
                    <option value="">كل الحالات</option>
                    @foreach (['new' => 'جديد', 'preparing' => 'قيد التحضير', 'ready' => 'جاهز', 'out_for_delivery' => 'خرج للتوصيل', 'completed' => 'مكتمل', 'cancelled' => 'ملغي'] as $key => $label)
                        <option value="{{ $key }}" @selected($selectedStatus === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary" id="restaurant-orders-filter-submit"><i class="ti ti-filter"></i> فلترة</button>
            </form>
            <div class="orders-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>الطلب</th>
                            <th>الوجبات</th>
                            <th>المجموع</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody id="restaurant-orders-body">
                        @include('admin.restaurant.partials.order_list_rows', [
                            'orders' => $orders,
                            'shop' => $shop,
                        ])
                    </tbody>
                </table>
            </div>
            <div>{{ $orders->links() }}</div>
        </section>
    </main>
    <script>
        (() => {
            const body = document.getElementById('restaurant-orders-body');
            const liveStatus = document.getElementById('live-status');
            const alarm = document.getElementById('restaurant-order-alarm');
            const alarmMessage = document.getElementById('restaurant-order-alarm-message');
            const soundToggle = document.getElementById('restaurant-sound-toggle');
            const filterForm = document.getElementById('restaurant-orders-filter');
            const filterSubmit = document.getElementById('restaurant-orders-filter-submit');
            const periodLabel = document.getElementById('stat-period-label');
            if (!body || !liveStatus || !alarm || !alarmMessage || !soundToggle || !filterForm) return;
            const feedEndpoint =
                {{ Illuminate\Support\Js::from(route('restaurant.orders.feed', ['shop' => $shop, 'period' => $selectedPeriod, 'status' => $selectedStatus, 'type' => $selectedType])) }};
            let feedUrl = feedEndpoint;
            const dashboardUrl = {{ Illuminate\Support\Js::from(route('restaurant.dashboard', ['shop' => $shop, 'period' => $selectedPeriod, 'status' => $selectedStatus, 'type' => $selectedType])) }};
            const initialLatestId = Number({{ (int) $latestOrderId }});
            const acknowledgementKey = 'ozman.restaurant.{{ (int) $shop->id }}.acknowledged-order';
            let acknowledgedId = initialLatestId;
            try {
                const savedId = window.localStorage.getItem(acknowledgementKey);
                if (savedId === null) window.localStorage.setItem(acknowledgementKey, String(initialLatestId));
                else acknowledgedId = Number(savedId) || 0;
            } catch (_) {}
            let pendingOrderId = 0;
            let audioContext = null;
            let alarmOutput = null;
            let alarmTimer = null;
            let soundUnlocked = false;
            let polling = false;
            let statusUpdating = false;
            let feedController = null;

            const periodLabels = {
                today: 'طلبات اليوم',
                week: 'طلبات هذا الأسبوع',
                month: 'طلبات هذا الشهر',
                all: 'كل الطلبات'
            };

            function updateFeedUrl() {
                const params = new URLSearchParams(new FormData(filterForm));
                const nextUrl = new URL(feedEndpoint, window.location.origin);
                nextUrl.search = params.toString();
                feedUrl = nextUrl.toString();

                const dashboardUrl = new URL(window.location.href);
                ['period', 'type', 'status'].forEach(key => {
                    const value = params.get(key);
                    if (value) dashboardUrl.searchParams.set(key, value);
                    else dashboardUrl.searchParams.delete(key);
                });
                window.history.replaceState({}, '', dashboardUrl);

                if (periodLabel) periodLabel.textContent = periodLabels[params.get('period')] || periodLabels.today;
            }

            async function applyFilters() {
                updateFeedUrl();
                if (filterSubmit) {
                    filterSubmit.disabled = true;
                    filterSubmit.classList.add('is-loading');
                }
                await refreshOrders();
                if (filterSubmit) {
                    filterSubmit.disabled = false;
                    filterSubmit.classList.remove('is-loading');
                }
            }

            function getAlarmOutput() {
                if (alarmOutput) return alarmOutput;

                const compressor = audioContext.createDynamicsCompressor();
                compressor.threshold.setValueAtTime(-18, audioContext.currentTime);
                compressor.knee.setValueAtTime(12, audioContext.currentTime);
                compressor.ratio.setValueAtTime(8, audioContext.currentTime);
                compressor.attack.setValueAtTime(.003, audioContext.currentTime);
                compressor.release.setValueAtTime(.18, audioContext.currentTime);

                const masterGain = audioContext.createGain();
                masterGain.gain.setValueAtTime(.95, audioContext.currentTime);
                masterGain.connect(compressor);
                compressor.connect(audioContext.destination);
                alarmOutput = masterGain;

                return alarmOutput;
            }

            function playAlarmPulse() {
                if (!soundUnlocked || !audioContext || audioContext.state !== 'running') return;
                const now = audioContext.currentTime;
                const output = getAlarmOutput();

                [
                    [784, 0, .16],
                    [988, .18, .16],
                    [1318, .36, .2],
                    [988, .62, .22]
                ].forEach(([frequency, offset, duration]) => {
                    const noteGain = audioContext.createGain();
                    const startsAt = now + offset;
                    const endsAt = startsAt + duration;
                    noteGain.gain.setValueAtTime(.0001, startsAt);
                    noteGain.gain.exponentialRampToValueAtTime(.42, startsAt + .018);
                    noteGain.gain.setValueAtTime(.42, Math.max(startsAt + .02, endsAt - .055));
                    noteGain.gain.exponentialRampToValueAtTime(.0001, endsAt);
                    noteGain.connect(output);

                    [
                        [frequency, 'triangle', 1],
                        [frequency * 2, 'sine', .22]
                    ].forEach(([tone, type, level]) => {
                        const oscillator = audioContext.createOscillator();
                        const toneGain = audioContext.createGain();
                        oscillator.type = type;
                        oscillator.frequency.setValueAtTime(tone, startsAt);
                        toneGain.gain.setValueAtTime(level, startsAt);
                        oscillator.connect(toneGain);
                        toneGain.connect(noteGain);
                        oscillator.start(startsAt);
                        oscillator.stop(endsAt + .02);
                    });
                });

                if (document.visibilityState !== 'visible') navigator.vibrate?.([180, 80, 180]);
            }

            async function enableSound(preview = false) {
                try {
                    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContextClass) throw new Error('unsupported');
                    audioContext ||= new AudioContextClass();
                    if (audioContext.state === 'suspended') await audioContext.resume();
                    soundUnlocked = audioContext.state === 'running';
                    if (soundUnlocked) {
                        soundToggle.classList.add('enabled');
                        soundToggle.classList.remove('attention');
                        soundToggle.innerHTML = '<i class="ti ti-volume-2"></i> صوت الطلبات مفعّل';
                        if (preview || pendingOrderId) playAlarmPulse();
                    }
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    soundToggle.innerHTML = '<i class="ti ti-volume-off"></i> اضغط للسماح بالصوت';
                }
            }

            function startAlarm() {
                if (alarmTimer) return;
                if (!soundUnlocked) soundToggle.classList.add('attention');
                playAlarmPulse();
                alarmTimer = window.setInterval(playAlarmPulse, 1200);
            }

            function stopAlarm() {
                if (alarmTimer) window.clearInterval(alarmTimer);
                alarmTimer = null;
            }

            function showOrderAlarm(order) {
                const orderId = Number(order?.id || 0);
                if (!orderId || orderId <= acknowledgedId) return;
                pendingOrderId = Math.max(pendingOrderId, orderId);
                const types = {
                    dine_in: 'طلب طاولة',
                    delivery: 'طلب توصيل',
                    pickup: 'طلب استلام'
                };
                alarmMessage.textContent =
                    `${order.number || 'طلب جديد'} · ${types[order.type] || 'طلب مطعم'}${order.customer ? ` · ${order.customer}` : ''}`;
                alarm.hidden = false;
                document.title = '🔔 طلب جديد — ' + {{ Illuminate\Support\Js::from($shop->name) }};
                liveStatus.innerHTML = '<i class="live-dot"></i> وصل طلب جديد — بانتظار التأكيد';
                liveStatus.style.color = 'var(--yellow)';
                startAlarm();
            }

            soundToggle.addEventListener('click', () => enableSound(true));
            const unlockOnFirstInteraction = () => enableSound(false);
            document.addEventListener('pointerdown', unlockOnFirstInteraction, {
                once: true,
                capture: true
            });
            document.addEventListener('keydown', unlockOnFirstInteraction, {
                once: true,
                capture: true
            });

            alarm.addEventListener('click', () => {
                const orderId = pendingOrderId;
                acknowledgedId = Math.max(acknowledgedId, orderId);
                try {
                    window.localStorage.setItem(acknowledgementKey, String(acknowledgedId));
                } catch (_) {}
                alarm.hidden = true;
                pendingOrderId = 0;
                stopAlarm();
                soundToggle.classList.remove('attention');
                document.title = {{ Illuminate\Support\Js::from('إدارة مطعم ' . $shop->name) }};
                liveStatus.innerHTML = '<i class="live-dot"></i> تم فتح الطلب الجديد';
                liveStatus.style.color = 'var(--green)';
                const row = document.getElementById(`restaurant-order-${orderId}`);
                if (row) {
                    row.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    row.style.background = 'rgba(255,212,59,.12)';
                } else if (orderId) window.location.href = `${dashboardUrl}#restaurant-order-${orderId}`;
            });

            body.addEventListener('click', event => {
                const row = event.target.closest('.order-summary-row[data-order-url]');
                if (!row || event.target.closest('a,button,input,select,label')) return;
                window.location.href = row.dataset.orderUrl;
            });

            body.addEventListener('keydown', event => {
                const row = event.target.closest('.order-summary-row[data-order-url]');
                if (!row || !['Enter', ' '].includes(event.key)) return;
                event.preventDefault();
                window.location.href = row.dataset.orderUrl;
            });

            body.addEventListener('click', event => {
                const choice = event.target.closest('.preparation-choice');
                if (!choice || choice.disabled) return;

                const form = choice.closest('.status-form');
                const minutes = Number(choice.dataset.preparationMinutes);
                const value = form?.querySelector('input[name="estimated_preparation_minutes"]');
                const saveButton = form?.querySelector('.status-save');
                if (!form || !value || !saveButton || !Number.isInteger(minutes)) return;

                value.value = String(minutes);
                form.querySelectorAll('.preparation-choice').forEach(button => {
                    const selected = button === choice;
                    button.classList.toggle('is-selected', selected);
                    button.setAttribute('aria-pressed', selected ? 'true' : 'false');
                });

                form.requestSubmit(saveButton);
            });

            body.addEventListener('submit', async event => {
                const form = event.target.closest('.status-form');
                if (!form) return;
                event.preventDefault();
                if (statusUpdating) return;

                const submitter = event.submitter || form.querySelector('.status-save');
                const payload = new FormData(form);
                payload.set('status', submitter.value);
                const feedback = form.querySelector('.status-feedback');
                const buttons = [...form.querySelectorAll('button:not(:disabled)')];
                statusUpdating = true;
                form.classList.add('is-saving');
                buttons.forEach(button => button.disabled = true);
                feedback.textContent = 'جارٍ حفظ حالة الطلب...';
                feedback.classList.remove('is-error');

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: payload,
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                    });
                    if (response.redirected || response.status === 401 || response.status === 403) {
                        throw new Error('انتهت الجلسة أو لا توجد صلاحية. حدّث الصفحة وسجّل الدخول مجددًا.');
                    }
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        const validationError = Object.values(data.errors || {}).flat()[0];
                        throw new Error(validationError || (response.status === 419 ?
                            'انتهت الجلسة؛ حدّث الصفحة وحاول مجددًا.' : data.message ||
                            'تعذّر حفظ حالة الطلب. حاول مجددًا.'));
                    }
                    const replacement = document.createElement('tbody');
                    replacement.innerHTML = data.html || '';
                    const updatedRow = replacement.querySelector('tr');
                    if (!updatedRow) throw new Error('تم الحفظ، لكن تعذّر تحديث العرض. حدّث الصفحة.');
                    form.closest('tr').replaceWith(updatedRow);
                    const updatedFeedback = updatedRow.querySelector('.status-feedback');
                    if (updatedFeedback) updatedFeedback.textContent = 'تم الحفظ وإشعار العميل.';
                } catch (error) {
                    feedback.textContent = error.message;
                    feedback.classList.add('is-error');
                } finally {
                    statusUpdating = false;
                    form.classList.remove('is-saving');
                    buttons.forEach(button => button.disabled = false);
                }
            });

            async function refreshOrders() {
                if (polling) feedController?.abort();
                const requestUrl = feedUrl;
                const controller = new AbortController();
                feedController = controller;
                polling = true;
                try {
                    const response = await fetch(requestUrl, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin',
                        cache: 'no-store',
                        signal: controller.signal
                    });
                    if (response.status === 401 || response.status === 403) {
                        liveStatus.innerHTML = '<i class="live-dot"></i> لا توجد صلاحية أو انتهت الجلسة';
                        liveStatus.style.color = 'var(--red)';
                        return
                    }
                    if (!response.ok) throw new Error('feed');
                    const data = await response.json();
                    if (requestUrl !== feedUrl) return;
                    const editingOrder = body.contains(document.activeElement);
                    if (!editingOrder && !statusUpdating) body.innerHTML = data.html;
                    for (const key of ['orders', 'new', 'preparing', 'ready']) {
                        const element = document.getElementById(`stat-${key}`);
                        if (element) element.textContent = data.stats[key] ?? 0
                    }
                    const salesTotal = document.getElementById('stat-sales-total');
                    if (salesTotal) salesTotal.textContent =
                        `${Number(data.stats.sales_total??0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})} ₪`;
                    if (Number(data.latest_id) > acknowledgedId) showOrderAlarm(data.latest_order);
                    else if (!pendingOrderId) {
                        liveStatus.innerHTML = '<i class="live-dot"></i> متصل وتحديث مباشر';
                        liveStatus.style.color = 'var(--green)';
                    }
                } catch (_) {
                    liveStatus.innerHTML = '<i class="live-dot"></i> جاري إعادة الاتصال';
                    liveStatus.style.color = 'var(--yellow)'
                } finally {
                    if (feedController === controller) {
                        feedController = null;
                        polling = false;
                    }
                }
            }
            filterForm.addEventListener('submit', event => {
                event.preventDefault();
                applyFilters();
            });
            filterForm.querySelectorAll('select').forEach(select => {
                select.addEventListener('change', applyFilters);
            });
            window.setInterval(refreshOrders, 3000);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) refreshOrders()
            });
        })();
    </script>
</body>

</html>
