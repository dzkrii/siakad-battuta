export default function WhatsAppHelpdesk() {
    const phoneNumber = '6285362489310'; // Nomor WhatsApp Anda
    const message = ''; // Pesan default

    return (
        <div className="tooltip" style={{ position: 'fixed', bottom: '20px', right: '20px', zIndex: 1000 }}>
            <a
                href={`https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`}
                target="_blank"
                rel="noopener noreferrer"
                style={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    width: '60px',
                    height: '60px',
                    backgroundColor: '#25D366',
                    borderRadius: '50%',
                    boxShadow: '0 4px 8px rgba(0, 0, 0, 0.2)',
                    transition: 'background-color 0.3s ease',
                }}
                onMouseEnter={(e) => (e.currentTarget.style.backgroundColor = '#128C7E')}
                onMouseLeave={(e) => (e.currentTarget.style.backgroundColor = '#25D366')}
            >
                <img
                    src="/images/whatsapp-icon.png"
                    alt="WhatsApp Helpdesk"
                    style={{ width: '30px', height: '30px' }}
                />
                <span className="tooltiptext">Butuh Bantuan?</span>
            </a>
        </div>
    );
}
