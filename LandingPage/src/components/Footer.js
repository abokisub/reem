import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { FaFacebook, FaTwitter, FaLinkedin, FaInstagram, FaWhatsapp } from 'react-icons/fa';
import logo from '../assets/logo.png';
import './Footer.css';

const Footer = () => {
  const [email, setEmail] = useState('');
  const [subscribed, setSubscribed] = useState(false);

  const handleSubscribe = (e) => {
    e.preventDefault();
    if (email) {
      // TODO: Implement newsletter subscription
      setSubscribed(true);
      setEmail('');
      setTimeout(() => setSubscribed(false), 3000);
    }
  };

  return (
    <footer className="footer">
      {/* Newsletter Section */}
      <div className="newsletter-section">
        <div className="container">
          <div className="newsletter-content">
            <div className="newsletter-text">
              <h3>Subscribe to our Newsletter</h3>
              <p>Stay informed about the latest updates, financial results, and announcements.</p>
            </div>
            <form className="newsletter-form" onSubmit={handleSubscribe}>
              <input
                type="email"
                placeholder="Enter your email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
              />
              <button type="submit" className="btn btn-primary">
                {subscribed ? 'Subscribed!' : 'Subscribe'}
              </button>
            </form>
          </div>
        </div>
      </div>

      {/* Main Footer */}
      <div className="footer-main">
        <div className="container">
          <div className="footer-grid">
            {/* Company Info */}
            <div className="footer-column">
              <div className="footer-logo">
                <img src={logo} alt="PointWave Logo" className="logo-image" />
              </div>
              <p className="footer-description">
                PointWave Digital Innovations - Effortless Payments and Secure Transactions Built on Trust.
              </p>
              <div className="social-links">
                <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                  <FaFacebook />
                </a>
                <a href="https://twitter.com" target="_blank" rel="noopener noreferrer" aria-label="Twitter">
                  <FaTwitter />
                </a>
                <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
                  <FaLinkedin />
                </a>
                <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                  <FaInstagram />
                </a>
              </div>
            </div>

            {/* Quick Links */}
            <div className="footer-column">
              <h4>Company</h4>
              <ul>
                <li><Link to="/company">About Us</Link></li>
                <li><Link to="/company#team">Our Team</Link></li>
                <li><Link to="/company#careers">Careers</Link></li>
                <li><Link to="/company#press">Press Kit</Link></li>
              </ul>
            </div>

            {/* Resources */}
            <div className="footer-column">
              <h4>Resources</h4>
              <ul>
                <li><a href="https://app.pointwave.ng/documentation/home">API Documentation</a></li>
                <li><Link to="/developers">Developer Portal</Link></li>
                <li><Link to="/pricing">Pricing</Link></li>
                <li><Link to="/support">Support Center</Link></li>
              </ul>
            </div>

            {/* Contact */}
            <div className="footer-column">
              <h4>Contact Us</h4>
              <ul className="contact-list">
                <li>
                  <strong>Phone:</strong>
                  <a href="tel:02014542876">02014542876</a>
                </li>
                <li>
                  <strong>Email:</strong>
                  <a href="mailto:support@pointwave.ng">support@pointwave.ng</a>
                </li>
                <li>
                  <strong>Location:</strong>
                  <span>Kano State, Nigeria</span>
                </li>
                <li>
                  <a 
                    href="https://wa.me/2348012345678" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    className="whatsapp-link"
                  >
                    <FaWhatsapp /> Join WhatsApp Community
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      {/* Footer Bottom */}
      <div className="footer-bottom">
        <div className="container">
          <div className="footer-bottom-content">
            <p>&copy; {new Date().getFullYear()} PointWave Digital Innovations. All Rights Reserved.</p>
            <div className="footer-links">
              <Link to="/privacy">Privacy Policy</Link>
              <Link to="/terms">Terms & Conditions</Link>
              <Link to="/cookie">Cookie Notice</Link>
              <Link to="/copyright">Copyright Policy</Link>
              <Link to="/data">Data Policy</Link>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
