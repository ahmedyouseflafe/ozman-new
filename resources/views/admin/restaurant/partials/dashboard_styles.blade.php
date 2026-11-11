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

        .hero-top { flex-wrap: wrap; }
        .hero-top > :first-child { flex: 1 1 270px; min-width: 0; }
        .hero-actions { flex: 2 1 580px; flex-wrap: wrap; justify-content: flex-start; }
        @media(max-width:900px) {
            .hero-top > :first-child, .hero-actions { flex: 0 1 auto; }
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
        /* Quick order controls in the list: status buttons, prep time and countdown. */
        .order-status-cell { min-width: 230px }
        .quick-status { display: grid; gap: 8px; margin-top: 8px }
        .quick-prep small { display: block; margin-bottom: 6px; color: var(--muted); font-size: 11.5px }
        .quick-prep .preparation-options { grid-template-columns: repeat(4, minmax(0, 1fr)); max-width: 260px }
        .quick-prep .preparation-choice { aspect-ratio: auto; min-height: 38px; grid-auto-flow: column; gap: 3px; border-radius: 11px; color: #e8f6ff }
        .quick-prep .preparation-choice b { font-size: 14px }
        .quick-prep .preparation-choice small { font-size: 10px; color: var(--muted) }
        .quick-prep .preparation-choice:hover { border-color: var(--yellow); color: var(--yellow) }
        .quick-actions { display: flex; gap: 6px; flex-wrap: wrap }
        .quick-btn { display: inline-flex; align-items: center; gap: 5px; min-height: 36px; padding: 0 14px; border-radius: 11px; border: 1px solid var(--border); background: rgba(255,255,255,.04); color: #fff; font: inherit; font-size: 12.5px; font-weight: 900; cursor: pointer }
        .quick-ready { border: 0; background: linear-gradient(135deg, #25df87, #08dcf4); color: #03201a }
        .quick-cancel { color: #ffb1bc; border-color: rgba(255, 98, 116, .4); background: rgba(255, 98, 116, .08) }
        .quick-link { justify-self: start; padding: 0; border: 0; background: none; color: var(--cyan); font: inherit; font-size: 12px; font-weight: 800; cursor: pointer; text-decoration: underline }
        .prep-timer { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 14px; border: 1px solid rgba(255, 212, 59, .35); background: rgba(255, 212, 59, .08); color: var(--yellow); width: max-content }
        .prep-timer i { font-size: 18px }
        .prep-timer-value { font-size: 22px; font-weight: 900; letter-spacing: .04em; font-variant-numeric: tabular-nums; direction: ltr }
        .prep-timer-chosen { padding: 2px 8px; border-radius: 20px; background: rgba(255, 212, 59, .15); font-size: 11.5px; font-weight: 900 }
        .prep-timer.is-soon { border-color: rgba(255, 140, 60, .6); color: #ff9b4a; background: rgba(255, 140, 60, .1) }
        .prep-timer.is-late { border-color: rgba(255, 98, 116, .6); color: var(--red); background: rgba(255, 98, 116, .1); animation: prepLate 1.2s ease-in-out infinite }
        @keyframes prepLate { 50% { box-shadow: 0 0 0 4px rgba(255, 98, 116, .18) } }
        .status-tag-preparing { color: var(--yellow); background: rgba(255, 212, 59, .1); border-color: rgba(255, 212, 59, .3) }
        .status-tag-ready, .status-tag-completed { color: var(--green); background: rgba(37, 223, 135, .1); border-color: rgba(37, 223, 135, .3) }
        .status-tag-cancelled { color: var(--red); background: rgba(255, 98, 116, .1); border-color: rgba(255, 98, 116, .3) }
        @media (prefers-reduced-motion: reduce) { .prep-timer.is-late { animation: none } }

        /* ===== Restaurant dashboard v2: compact header, filter tiles, order cards ===== */
        .rd-hero { padding: 18px 22px; display: grid; gap: 14px }
        .rd-hero-top { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; position: relative }
        .rd-title { min-width: 0 }
        .rd-title h1 { margin: 2px 0 0; font-size: clamp(22px, 2.2vw, 32px); line-height: 1.3 }
        .rd-main-actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: stretch }
        .rd-main-actions .availability-form { margin: 0 }
        .rd-main-actions .availability-toggle { min-height: 52px; padding: 6px 16px; text-align: start }
        .rd-main-actions .availability-toggle > span:last-child { display: grid; line-height: 1.25 }
        .rd-main-actions .availability-toggle b { font-size: 14px }
        .rd-main-actions .availability-toggle small { font-size: 11px; opacity: .75; font-weight: 700 }
        .rd-main-actions .btn-primary { min-height: 52px }
        .rd-links { display: flex; gap: 8px; overflow-x: auto; scrollbar-width: none; position: relative; padding-bottom: 2px }
        .rd-links::-webkit-scrollbar { display: none }
        .rd-links a { flex: 0 0 auto; display: inline-flex; align-items: center; gap: 6px; padding: 7px 13px; border-radius: 999px; border: 1px solid var(--border); background: rgba(255,255,255,.03); color: #dbe7ef; text-decoration: none; font-size: 13px; font-weight: 800; white-space: nowrap }
        .rd-links a:hover { border-color: var(--cyan); color: var(--cyan) }
        .rd-links i { font-size: 16px; color: var(--cyan) }

        .rd-stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px }
        .rd-stat { display: grid; gap: 4px; text-align: start; padding: 14px 16px; border-radius: 18px; border: 1px solid var(--border); background: linear-gradient(135deg, rgba(25, 23, 39, .91), rgba(11, 25, 28, .9)); color: #fff; font: inherit; cursor: pointer; position: relative; overflow: hidden; transition: border-color .15s, transform .15s }
        .rd-stat::before { content: ""; position: absolute; inset: 0 0 auto auto; width: 4px; height: 100%; background: var(--accent) }
        .rd-stat > span { color: var(--muted); font-size: 12.5px; font-weight: 800; display: flex; align-items: center; gap: 6px }
        .rd-stat > span i { color: var(--accent); font-size: 17px }
        .rd-stat strong { font-size: 28px; line-height: 1.1; color: var(--accent); font-variant-numeric: tabular-nums }
        button.rd-stat:hover { border-color: var(--accent); transform: translateY(-1px) }
        .rd-stat.is-active { border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent) inset, 0 10px 30px rgba(0,0,0,.25) }
        .rd-stat-sales { cursor: default }
        .rd-stat-sales strong { font-size: 22px; direction: rtl }

        .rd-filters { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin: 16px 0 }
        .rd-filters .field { min-height: 44px }
        .rd-filters .btn { display: none }
        .live-tools .sound-toggle { min-height: 40px; font-size: 13px }

        .order-board { display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 14px; align-items: start }
        .order-card { position: relative; display: grid; gap: 10px; padding: 16px; border-radius: 20px; border: 1px solid var(--border); background: rgba(8, 12, 18, .75); cursor: pointer; transition: border-color .15s, background .15s; scroll-margin: 90px }
        .order-card:hover { border-color: rgba(8, 220, 244, .45) }
        .order-card::before { content: ""; position: absolute; inset: 14px 0 14px auto; width: 4px; border-radius: 4px 0 0 4px; background: var(--muted) }
        .order-card-new::before { background: var(--green) } .order-card-preparing::before { background: var(--yellow) }
        .order-card-ready::before { background: #b388ff } .order-card-out_for_delivery::before { background: var(--cyan) } .order-card-cancelled::before { background: var(--red) }
        .order-card-new { border-color: rgba(37, 223, 135, .45); background: linear-gradient(160deg, rgba(37, 223, 135, .08), rgba(8, 12, 18, .8) 45%) }
        .order-card.is-finished { opacity: .62 }
        .order-card.is-finished:hover { opacity: 1 }
        .order-card.is-flash { animation: ocFlash 1.6s ease 2 }
        @keyframes ocFlash { 50% { box-shadow: 0 0 0 4px rgba(255, 212, 59, .45) } }
        .oc-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px }
        .oc-id { display: grid; gap: 4px; min-width: 0 }
        .oc-id .order-number-link { color: #fff; font-size: 16px; font-weight: 900; text-decoration: none; letter-spacing: .02em }
        .oc-type { display: inline-flex; align-items: center; gap: 5px; width: max-content; max-width: 100%; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 900; background: rgba(8, 220, 244, .1); color: var(--cyan) }
        .oc-type-delivery { background: rgba(255, 159, 67, .12); color: #ffb26b } .oc-type-dine_in { background: rgba(179, 136, 255, .14); color: #c9a8ff }
        .oc-status { flex: 0 0 auto; display: inline-flex; align-items: center; gap: 7px; padding: 5px 12px; border-radius: 10px; font-size: 13px; font-weight: 900; color: var(--green); background: rgba(37, 223, 135, .1); pointer-events: none; user-select: none }
        .oc-status i { width: 8px; height: 8px; border-radius: 50%; background: currentColor; box-shadow: 0 0 0 3px color-mix(in srgb, currentColor 25%, transparent) }
        .oc-status.status-tag-preparing { color: var(--yellow); background: rgba(255, 212, 59, .1) }
        .oc-status.status-tag-cancelled { color: var(--red); background: rgba(255, 98, 116, .1) }
        .oc-status.status-tag-ready { color: #c9a8ff; background: rgba(179, 136, 255, .12) }
        .oc-status.status-tag-out_for_delivery { color: var(--cyan); background: rgba(8, 220, 244, .1) }
        .oc-status.status-tag-completed { color: #9fb3c2; background: rgba(255,255,255,.06) }
        .oc-meta { display: flex; flex-wrap: wrap; gap: 6px 14px; color: var(--muted); font-size: 12.5px; font-weight: 700 }
        .oc-meta span, .oc-meta a { display: inline-flex; align-items: center; gap: 4px }
        .oc-meta a { color: var(--cyan); text-decoration: none }
        .oc-address { display: flex; gap: 6px; align-items: flex-start; padding: 8px 10px; border-radius: 12px; background: rgba(255, 159, 67, .07); color: #ffd5ae; font-size: 13px; font-weight: 700 }
        .oc-address i { margin-top: 3px }
        .oc-items { display: grid; gap: 5px; padding: 10px 0; border-block: 1px dashed rgba(139, 160, 179, .2) }
        .oc-item { display: flex; gap: 7px; font-size: 14.5px; font-weight: 800; line-height: 1.5 }
        .oc-item b { color: var(--cyan); min-width: 24px }
        .oc-item small { display: block; color: var(--muted); font-size: 11.5px; font-weight: 600 }
        .oc-items .more-items { color: var(--cyan); font-size: 12px; font-weight: 800; text-decoration: none }
        .oc-gift { color: #51e4b0; font-size: 13px; font-weight: 800 }
        .oc-note { color: #ffe29a; font-size: 12.5px; font-weight: 700 }
        .oc-muted { color: var(--muted); font-size: 13px }
        .oc-steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; margin: 0; padding: 0; list-style: none; counter-reset: s }
        .oc-steps li { position: relative; padding-top: 10px; text-align: center; font-size: 11px; font-weight: 800; color: rgba(155, 167, 180, .6) }
        .oc-steps li::before { content: ""; position: absolute; top: 0; inset-inline: 0; height: 5px; border-radius: 5px; background: rgba(139, 160, 179, .18) }
        .oc-steps li.done { color: var(--green) } .oc-steps li.done::before { background: var(--green) }
        .oc-steps li.current { color: #fff } .oc-steps li.current::before { background: linear-gradient(90deg, var(--yellow), var(--green)) }
        .oc-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px }
        .oc-total { display: grid; font-size: 19px; font-weight: 900; color: var(--yellow); line-height: 1.2 }
        .oc-total small { color: var(--muted); font-size: 11px; font-weight: 700 }
        .oc-details { color: var(--cyan); text-decoration: none; font-size: 13px; font-weight: 800; display: inline-flex; align-items: center; gap: 2px }
        .order-card .quick-status { margin: 0; padding: 12px; border-radius: 16px; background: rgba(255, 255, 255, .03); border: 1px solid rgba(139, 160, 179, .14); cursor: default }
        .order-card .quick-prep small { color: var(--muted); font-weight: 800 }
        .order-card .quick-prep .preparation-options { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 6px; margin-top: 6px }
        .order-card .quick-prep .preparation-choice { min-height: 42px }
        .order-card .quick-prep .preparation-choice.is-selected { border-color: var(--yellow); color: var(--yellow); background: rgba(255, 212, 59, .1) }
        .oc-timer-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap }
        .order-card .quick-link { text-decoration: none; display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; border-radius: 10px; border: 1px solid rgba(8, 220, 244, .3) }
        .order-card .quick-actions { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap }
        .order-card .quick-ready { flex: 1 1 auto; min-height: 46px; justify-content: center; font-size: 14.5px; border-radius: 13px }
        .order-card .quick-cancel { margin-inline-start: auto; display: inline-flex; align-items: center; gap: 4px; padding: 6px 4px; border: 0; background: none; color: #ff8d9b; font: inherit; font-size: 12.5px; font-weight: 800; cursor: pointer }
        .order-card .quick-cancel:hover { text-decoration: underline }
        .oc-wait { flex: 1 1 auto; display: flex; align-items: center; gap: 7px; padding: 10px 12px; border-radius: 12px; background: rgba(8, 220, 244, .07); color: #bdf6ff; font-size: 13px; font-weight: 700; cursor: default }
        .oc-wait i { font-size: 19px; color: var(--cyan) }
        .oc-hint { color: var(--muted); font-size: 11.5px }
        .order-card .status-feedback:empty { display: none }
        .orders-empty { grid-column: 1 / -1; display: grid; justify-items: center; gap: 4px; padding: 40px 16px; border: 1px dashed var(--border); border-radius: 20px; color: var(--muted); text-align: center }
        .orders-empty i { font-size: 34px; color: var(--cyan) }
        .orders-empty b { color: #fff; font-size: 16px }

        @media (max-width: 1100px) { .rd-stats { grid-template-columns: repeat(4, minmax(0, 1fr)) } .rd-stat-sales { grid-column: 1 / -1 } }
        @media (max-width: 900px) {
            .rd-hero { padding: 16px; border-radius: 20px }
            .rd-hero-top { display: grid; grid-template-columns: 1fr; gap: 12px }
            .rd-main-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px }
            .rd-main-actions .availability-toggle, .rd-main-actions .btn-primary { width: 100%; min-height: 50px; padding: 6px 10px; font-size: 13px }
            .rd-links { margin-inline: -16px; padding-inline: 16px }
            .rd-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px }
            .rd-stat { padding: 10px 8px 10px 10px; border-radius: 14px; gap: 2px }
            .rd-stat > span { font-size: 11px; gap: 3px }
            .rd-stat > span i { font-size: 14px }
            .rd-stat strong { font-size: 22px }
            .rd-stat-sales { display: flex; align-items: center; justify-content: space-between }
            .rd-stat-sales strong { font-size: 18px }
            .live-heading { display: grid !important; gap: 10px }
            .live-tools { display: flex !important; flex-wrap: wrap; gap: 8px; justify-content: space-between; align-items: center }
            .rd-filters { grid-template-columns: 1fr 1fr; margin: 12px 0 }
            .rd-filters select[name="status"] { grid-column: 1 / -1 }
            .order-board { grid-template-columns: 1fr; gap: 12px }
            .order-card { padding: 14px; border-radius: 18px }
        }
        @media (max-width: 380px) { .rd-stat > span { font-size: 10px } .rd-stat strong { font-size: 19px } }

        /* ===== Compact order cards: summary first, details on tap ===== */
        .order-board { grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 12px }
        .order-card { gap: 8px; padding: 12px 14px; border-radius: 16px; cursor: pointer }
        .order-card::before { inset: 12px 0 12px auto }
        .oc-head { display: grid; gap: 4px; cursor: pointer; border-radius: 10px; outline: none; color: inherit; text-decoration: none }
        .oc-head:focus-visible { box-shadow: 0 0 0 2px var(--cyan) }
        .oc-line1 { display: flex; align-items: center; gap: 8px; min-width: 0 }
        .oc-number { font-size: 16px; font-weight: 900; letter-spacing: .03em; color: #fff }
        .oc-line1 .oc-type { padding: 1px 8px; font-size: 11.5px }
        .oc-line1 .oc-status { margin-inline-start: auto; padding: 3px 9px; font-size: 12px; border-radius: 8px }
        .oc-line2 { display: flex; align-items: center; gap: 6px; color: var(--muted); font-size: 12.5px; font-weight: 700; min-width: 0 }
        .oc-who { color: #dce7ee; max-width: 38%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap }
        .oc-dot { opacity: .5 }
        .oc-line2 .oc-total { margin-inline-start: auto; display: block; font-size: 15px; color: var(--yellow) }
        .oc-chevron { font-size: 16px; color: var(--cyan); transition: transform .2s }
        .order-card .quick-status { padding: 0; border: 0; background: none; gap: 8px }
        .order-card .quick-prep small { font-size: 11.5px }
        .order-card .quick-prep .preparation-options { display: flex; gap: 5px; overflow-x: auto; scrollbar-width: none; margin-top: 4px; padding-bottom: 2px }
        .order-card .quick-prep .preparation-options::-webkit-scrollbar { display: none }
        .order-card .quick-prep .preparation-choice { flex: 0 0 auto; min-width: 46px; min-height: 38px; padding: 0 8px }
        .order-card .prep-timer { width: 100%; padding: 6px 10px; border-radius: 12px }
        .order-card .prep-timer-value { font-size: 19px }
        .order-card .prep-timer .prep-edit { margin-inline-start: auto; padding: 4px 9px; font-size: 12px }
        .order-card .quick-actions { gap: 8px; flex-wrap: nowrap }
        .order-card .quick-ready { flex: 1 1 auto; min-height: 40px; font-size: 13.5px; border-radius: 11px }
        .order-card .oc-wait { flex: 1 1 auto; padding: 8px 10px; font-size: 12.5px; border-radius: 11px; min-width: 0 }
        .order-card .oc-wait span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap }
        .status-menu-toggle { flex: 0 0 auto; min-height: 40px !important; padding: 0 12px !important; font-size: 12.5px !important; color: var(--cyan) !important; border-color: rgba(8, 220, 244, .35) !important; background: rgba(8, 220, 244, .06) !important; margin-inline-start: auto }
        .status-menu-toggle[aria-expanded="true"] { background: rgba(8, 220, 244, .16) !important }
        .status-menu { display: flex; flex-wrap: wrap; gap: 6px; padding: 8px; border-radius: 12px; background: rgba(255, 255, 255, .03); border: 1px solid rgba(139, 160, 179, .16) }
        .status-menu[hidden] { display: none }
        .status-move { min-height: 36px; padding: 0 12px; border-radius: 10px; border: 1px solid var(--border); background: rgba(255, 255, 255, .04); color: #e8f6ff; font: inherit; font-size: 12.5px; font-weight: 800; cursor: pointer }
        .status-move:hover { border-color: var(--cyan); color: var(--cyan) }
        .status-move-new { color: var(--green) } .status-move-preparing { color: var(--yellow) } .status-move-ready { color: #c9a8ff }
        .status-move-completed { color: var(--green) } .status-move-cancelled { color: #ff8d9b; border-color: rgba(255, 98, 116, .35); margin-inline-start: auto }
        .order-card .status-feedback { font-size: 12px; color: var(--green) }
        .order-card .status-feedback.is-error { color: var(--red) }
        .order-card.is-finished { opacity: .7 }
        @media (max-width: 900px) { .order-board { grid-template-columns: 1fr; gap: 10px } .order-card { padding: 12px } }
    </style>
