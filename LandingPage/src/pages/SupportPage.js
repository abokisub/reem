import React, { useState } from 'react';
import Navbar from '../components/Navbar';
import Footer from '../components/Footer';
import './SupportPage.css';

const SupportPage = () => {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    subject: '',
    message: ''
  });

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    // Handle form submission - you can integrate with your backend
    alert('Thank you for contacting us! We will get back to you soon.');
    setFormData({ name: '', email: '', subject: '', message: '' });
  };

  const faqs = [
    {
      question: 'How do I get started with PointWave?',
      answer: 'Simply create a free account, verify your business details, and you\'ll receive your API keys instantly. You can start integrating right away using our comprehensive documentation.'
    },
    {
      question: 'What are the transaction fees?',
      answer: 'We charge 0.5% per transaction (capped at ₦500) for virtual account payments. Other services like bank transfers and KYC verification have variable fees. Check our Pricing page for details.'
    },
    {
      question: 'How long does settlement take?',
      answer: 'Standard settlement is within 24 hours (excluding weekends and holidays). Enterprise customers can request custom settlement schedules.'
    },
    {
      question: 'Is there a sandbox environment for testing?',
      answer: 'Yes! We provide a full sandbox environment where you can test all features without processing real transactions. Perfect for development and testing.'
    },
    {
      question: 'What payment methods do you support?',
      answer: 'We support virtual accounts, bank transfers, and mobile payments through our partners PalmPay and 9PSB. More payment methods are coming soon.'
    },
    {
      question: 'How secure is PointWave?',
      answer: 'We use bank-grade security with end-to-end encryption, secure API authentication, and webhook signature verification. All transactions are monitored 24/7.'
    }
  ];

  const contactMethods = [
    {
      icon: '📧',
      title: 'Email Support',
      value: 'support@pointwave.ng',
      description: 'We typically respond within 24 hours'
    },
    {
      icon: '📞',
      title: 'Phone Support',
      value: '02014542876',
      description: 'Monday - Friday, 9AM - 5PM WAT'
    },
    {
      icon: '📍',
      title: 'Office Location',
      value: 'Kano State, Nigeria',
      description: 'Visit us during business hours'
    }
  ];

  return (
    <div className="support-page">
      <Navbar />
      <main>
        <section className="support-hero">
          <div className="container">
            <h1 className="page-title">How Can We Help?</h1>
            <p className="page-subtitle">
              Get in touch with our support team
            </p>
          </div>
        </section>

        <section className="contact-methods">
          <div className="container">
            <div className="methods-grid">
              {contactMethods.map((method, index) => (
                <div key={index} className="method-card">
                  <div className="method-icon">{method.icon}</div>
                  <h3 className="method-title">{method.title}</h3>
                  <p className="method-value">{method.value}</p>
                  <p className="method-description">{method.description}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        <section className="contact-form-section">
          <div className="container">
            <div className="form-wrapper">
              <h2 className="section-title">Send Us a Message</h2>
              <form onSubmit={handleSubmit} className="contact-form">
                <div className="form-row">
                  <div className="form-group">
                    <label htmlFor="name">Full Name</label>
                    <input
                      type="text"
                      id="name"
                      name="name"
                      value={formData.name}
                      onChange={handleChange}
                      required
                      placeholder="John Doe"
                    />
                  </div>
                  <div className="form-group">
                    <label htmlFor="email">Email Address</label>
                    <input
                      type="email"
                      id="email"
                      name="email"
                      value={formData.email}
                      onChange={handleChange}
                      required
                      placeholder="john@example.com"
                    />
                  </div>
                </div>
                <div className="form-group">
                  <label htmlFor="subject">Subject</label>
                  <input
                    type="text"
                    id="subject"
                    name="subject"
                    value={formData.subject}
                    onChange={handleChange}
                    required
                    placeholder="How can we help?"
                  />
                </div>
                <div className="form-group">
                  <label htmlFor="message">Message</label>
                  <textarea
                    id="message"
                    name="message"
                    value={formData.message}
                    onChange={handleChange}
                    required
                    rows="6"
                    placeholder="Tell us more about your inquiry..."
                  ></textarea>
                </div>
                <button type="submit" className="btn btn-primary btn-large">
                  Send Message
                </button>
              </form>
            </div>
          </div>
        </section>

        <section className="faqs-section">
          <div className="container">
            <h2 className="section-title">Frequently Asked Questions</h2>
            <div className="faqs-list">
              {faqs.map((faq, index) => (
                <div key={index} className="faq-item">
                  <h3 className="faq-question">{faq.question}</h3>
                  <p className="faq-answer">{faq.answer}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        <section className="support-cta">
          <div className="container">
            <div className="cta-box">
              <h2>Still Have Questions?</h2>
              <p>Check out our documentation or create an account to get started</p>
              <div className="cta-buttons">
                <a 
                  href="https://app.pointwave.ng/documentation/home" 
                  className="btn btn-outline btn-large"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  View Documentation
                </a>
                <a 
                  href="https://app.pointwave.ng/auth/register" 
                  className="btn btn-primary btn-large"
                >
                  Create Account
                </a>
              </div>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default SupportPage;
